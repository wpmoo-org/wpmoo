/**
 * WPMoo Gulp Build File
 * 
 * Handles SASS compilation, JS module injection, Pico CSS scoping,
 * and sets up a development server with BrowserSync.
 */

const { src, dest, watch, series } = require("gulp");
const { Transform } = require("stream");
const path = require("path");
const { fileURLToPath } = require("url");
const fs = require("fs");
const sass = require("sass");
const cleanCSS = require("gulp-clean-css");
const sourcemaps = require("gulp-sourcemaps");
const browserSync = require("browser-sync").create();

const paths = {
  styles: {
    entries: [
      "resources/scss/wpmoo.scss", // Main WPMoo scoped styles
    ],
    src: "resources/scss/**/*.scss",
    dest: "src/assets/css",
    bridgeOut: "wpmoo.bridge.css",
    finalOut: "wpmoo.css",
  },
  scripts: {
    entry: "resources/js/wpmoo.js",
    watch: "resources/js/**/*.js",
    dest: "src/assets/js",
  },
  html: {
    src: "/*.html",
    base: ".",
  },
  pico: {
    scoped: "vendor/pico/css/pico.conditional.css",
    dest: "src/assets",
    outFile: "pico-wpmoo.css",
  },
};

const MODULE_PLACEHOLDER = "/* @wpmoo-modules */";

function changeExtension(filePath, newExt) {
  const parsed = path.parse(filePath);
  parsed.base = parsed.name + newExt;
  parsed.ext = newExt;
  return path.format(parsed);
}

function compileSass(options = {}) {
  return new Transform({
    objectMode: true,
    transform(file, encoding, callback) {
      if (file.isNull()) {
        callback(null, file);
        return;
      }

      if (file.isStream()) {
        callback(new Error("Streaming not supported"));
        return;
      }

      if (path.basename(file.path).startsWith("_")) {
        callback();
        return;
      }

      const compileOptions = {
        style: options.style || "expanded",
        sourceMap: Boolean(file.sourceMap),
        sourceMapIncludeSources: Boolean(file.sourceMap),
        loadPaths: options.loadPaths,
        quietDeps: options.quietDeps,
      };

      sass
        .compileAsync(file.path, compileOptions)
        .then((result) => {
          file.contents = Buffer.from(result.css);
          file.path = changeExtension(file.path, ".css");

          if (file.sourceMap && result.sourceMap) {
            const map = result.sourceMap;
            map.file = changeExtension(file.relative, ".css");
            map.sources = map.sources.map((source) => {
              if (source.startsWith("file://")) {
                const osPath = fileURLToPath(source);
                return path.relative(file.base, osPath);
              }

              return source;
            });

            file.sourceMap = map;
          }

          callback(null, file);
        })
        .catch((error) => {
          callback(error);
        });
    },
  });
}

function styles() {
  return src(paths.styles.entries, { allowEmpty: true })
    .pipe(sourcemaps.init())
    .pipe(
      compileSass({
        style: "expanded",
        loadPaths: [path.resolve("node_modules")],
        quietDeps: true,
      })
    )
    .pipe(replaceText(/\/\*!([\s\S]*?)Pico CSS([\s\S]*?)\*\//g, ""))
    .pipe(replaceText(/\/\*!([\s\S]*?)WPMoo UI bundle([\s\S]*?)\*\//g, ""))
    .pipe(cleanCSS())

    /* Prepend attribution banner preserved after minify */
    .pipe(new Transform({
      objectMode: true,
      transform(file, enc, cb) {
        if (file.isNull()) return cb(null, file);
        if (file.isStream()) return cb(new Error("Streaming not supported"));
        const year = new Date().getFullYear();
        const banner =
          "/*!\n" +
          " * WPMoo UI Scoped Base\n" +
          ` * Copyright ${year} - Licensed under MIT\n` +
          " * Contains portions of Pico CSS (MIT). See LICENSE-PICO.md.\n" +
          " */\n";
        const css = file.contents.toString(enc || "utf8");
        file.contents = Buffer.from(banner + css);
        cb(null, file);
      },
    }))
    .pipe(sourcemaps.write("."))
    .pipe(dest(paths.styles.dest))
    .pipe(browserSync.stream({ match: "**/*.css" }));
}

/* Clean previous css outputs to avoid stale headers */
function cleanOut(done) {
  const out = path.join(paths.styles.dest, paths.styles.finalOut);
  const mapFile = out + ".map";
  try { if (fs.existsSync(out)) fs.unlinkSync(out); } catch (e) { }
  try { if (fs.existsSync(mapFile)) fs.unlinkSync(mapFile); } catch (e) { }
  done();
}

/* Text replace helper: swap :where(.scoped) → :where(.wpmoo) */
function replaceText(find, replaceWith) {
  return new Transform({
    objectMode: true,
    transform(file, enc, cb) {
      if (file.isNull()) return cb(null, file);
      if (file.isStream()) return cb(new Error("Streaming not supported"));
      let code = file.contents.toString(enc || "utf8");
      code = code.replace(find, replaceWith);
      file.contents = Buffer.from(code);
      cb(null, file);
    },
  });
}

function injectModules() {
  const modulesDir = path.resolve("resources/js/wpmoo");

  return new Transform({
    objectMode: true,
    transform(file, enc, cb) {
      if (file.isNull()) {
        return cb(null, file);
      }

      if (file.isStream()) {
        return cb(new Error("Streaming not supported"));
      }

      if (path.basename(file.path) !== "wpmoo.js") {
        return cb(null, file);
      }

      let modulesCode = "";
      if (fs.existsSync(modulesDir)) {
        const files = fs
          .readdirSync(modulesDir)
          .filter((filename) => filename.endsWith(".js"))
          .sort();

        modulesCode = files
          .map((filename) => fs.readFileSync(path.join(modulesDir, filename), "utf8"))
          .join("\n\n");
      }

      const placeholder = MODULE_PLACEHOLDER;
      let content = file.contents.toString(enc || "utf8");

      if (content.indexOf(placeholder) !== -1) {
        content = content.replace(placeholder, modulesCode);
      } else {
        content = modulesCode + "\n" + content;
      }

      file.contents = Buffer.from(content);
      cb(null, file);
    },
  });
}

function renameTo(newBaseName) {
  return new Transform({
    objectMode: true,
    transform(file, enc, cb) {
      const parsed = path.parse(file.path);
      parsed.base = newBaseName;
      file.path = path.format(parsed);
      cb(null, file);
    },
  });
}

/* Generate a Pico CSS variant scoped to .wpmoo */
function picoScope() {
  return src(paths.pico.scoped, { allowEmpty: true })
    .pipe(replaceText(/\.pico/g, ".wpmoo"))
    .pipe(replaceText(/--pico-/g, "--wpmoo-"))
    .pipe(renameTo(paths.pico.outFile))
    .pipe(dest(paths.pico.dest))
    .pipe(browserSync.stream({ match: "**/*.css" }));
}

/* Clean destination to avoid stale copies of individual module files. */
function scripts() {
  fs.rmSync(paths.scripts.dest, { recursive: true, force: true });

  return src(paths.scripts.entry, { allowEmpty: true })
    .pipe(injectModules())
    .pipe(dest(paths.scripts.dest))
    .pipe(browserSync.stream({ match: "**/*.js" }));
}

/* Copy third-party licenses into dist */
function copyLicenses() {
  const srcPath = path.resolve("vendor/pico/LICENSE.md");
  const exists = fs.existsSync(srcPath);
  if (!exists) return Promise.resolve();
  return src(srcPath).pipe(renameTo("LICENSE-PICO.md")).pipe(dest("dist"));
}

/* Determine HTTPS option for BrowserSync */
function getHttpsOption() {
  const httpOff = process.env.BS_HTTP;
  if (httpOff && ["1", "true", "on"].includes(String(httpOff).toLowerCase())) {
    return false;
  }

  const keyEnv = process.env.BS_HTTPS_KEY;
  const certEnv = process.env.BS_HTTPS_CERT;
  if (keyEnv && certEnv && fs.existsSync(keyEnv) && fs.existsSync(certEnv)) {
    return { key: fs.readFileSync(keyEnv), cert: fs.readFileSync(certEnv) };
  }

  const certDir = path.resolve(__dirname, "../../.dev/certs");
  const keyPath = path.join(certDir, "localhost-key.pem");
  const certPath = path.join(certDir, "localhost.pem");
  if (fs.existsSync(keyPath) && fs.existsSync(certPath)) {
    return { key: fs.readFileSync(keyPath), cert: fs.readFileSync(certPath) };
  }

  return true; // Browsersync self-signed (will warn)
}

/* Setup BrowserSync with WP local dev URL proxying */
function serve() {
  const httpsOpt = getHttpsOption();
  const loginUser = process.env.WP_DEV_USER || "";
  const loginPass = process.env.WP_DEV_PASS || "";
  const autoLoginEnv = String(process.env.BS_AUTO_LOGIN || "1").toLowerCase();
  const autoLoginOn = ["1", "true", "on", "yes"].includes(autoLoginEnv);
  const injectLogin = Boolean(loginUser && loginPass && autoLoginOn);

  const bsConfig = {
    proxy: "https://wp-dev.local",
    startPath: "/wp-admin/admin.php?page=wpmoo-samples",
    https: httpsOpt,
    open: "https://wp-dev.local", // Open main site URL
    notify: false,
  };

  if (injectLogin) {
    bsConfig.snippetOptions = {
      rule: {
        match: /<\/body>/i,
        fn: function (snippet, match) {
          const script = `\n<script>(function(){\n  try {\n    if (location.pathname.endsWith('/wp-login.php')) {\n      var u = ${JSON.stringify(loginUser)};\n      var p = ${JSON.stringify(loginPass)};\n      var user = document.getElementById('user_login');\n      var pass = document.getElementById('user_pass');\n      if (user && pass) {\n        user.value = u;\n        pass.value = p;\n        var form = document.getElementById('loginform');\n        if (form) { form.submit(); }\n      }\n    }\n  } catch(e) {}\n})();</script>\n`;
          return snippet + script + match;
        },
      },
    };
  }

  browserSync.init(bsConfig);

  /* Gulp watchers */
  watch(paths.styles.src, series(styles, copyLicenses));
  watch(paths.scripts.watch, scripts);

  /* Browser reload on PHP or HTML changes */
  watch(["**/*.php", paths.html.src]).on("change", browserSync.reload);
}


exports.styles = styles;
exports["pico:scope"] = picoScope;
exports.watch = series(cleanOut, styles, scripts, copyLicenses, serve);
exports.build = series(cleanOut, styles, scripts, copyLicenses);
exports.default = series(cleanOut, styles, scripts, copyLicenses);
