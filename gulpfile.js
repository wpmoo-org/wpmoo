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
      // Köprü/override girişiniz (wpmoo.scss → wpmoo.bridge.css, sonra bundle)
      "assets/scss/wpmoo.scss",
    ],
    src: "assets/scss/**/*.scss",
    dest: "assets/css",
    destAlt: "src/assets/css",
    bridgeOut: "wpmoo.bridge.css",
    finalOut: "wpmoo.css",
  },
  html: {
    src: "/*.html",
    base: ".",
  },
  pico: {
    scoped: "vendor/pico/css/pico.conditional.css",
    dest: "assets",
    destAlt: "src/assets",
    outFile: "pico-wpmoo.css",
  },
};

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
    // Remove upstream Pico header block if present (we add our own banner below)
    .pipe(replaceText(/\/\*!([\s\S]*?)Pico CSS([\s\S]*?)\*\//g, ""))
    // Remove any stale previous WPMoo bundle banner
    .pipe(replaceText(/\/\*!([\s\S]*?)WPMoo UI bundle([\s\S]*?)\*\//g, ""))
    .pipe(cleanCSS())
    // Prepend attribution banner preserved after minify
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
    .pipe(dest(paths.styles.destAlt))
    .pipe(browserSync.stream({ match: "**/*.css" }));
}

// Clean previous css outputs to avoid stale headers
function cleanOut(done) {
  const out = path.join(paths.styles.dest, paths.styles.finalOut);
  const mapFile = out + ".map";
  try { if (fs.existsSync(out)) fs.unlinkSync(out); } catch (e) { }
  try { if (fs.existsSync(mapFile)) fs.unlinkSync(mapFile); } catch (e) { }
  done();
}

// Text replace helper: swap :where(.scoped) → :where(.wpmoo)
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

function picoScope() {
  return src(paths.pico.scoped, { allowEmpty: true })
    .pipe(replaceText(/\.pico/g, ".wpmoo"))
    // Rename Pico custom properties to wpmoo-prefixed to avoid 'pico' mentions in CSS
    .pipe(replaceText(/--pico-/g, "--wpmoo-"))
    // Optional: debrand header line while keeping MIT license elsewhere in repo
    // .pipe(replaceText(/Pico CSS[^\n]*/g, "WPMoo UI Scoped Base"))
    // .pipe(replaceText(/picocss\.com/g, "wpmoo.org/ui"))
    .pipe(renameTo(paths.pico.outFile))
    .pipe(dest(paths.pico.dest))
    .pipe(dest(paths.pico.destAlt))
    .pipe(browserSync.stream({ match: "**/*.css" }));
}

// Copy third-party licenses into dist
function copyLicenses() {
  const srcPath = path.resolve("vendor/pico/LICENSE.md");
  const exists = fs.existsSync(srcPath);
  if (!exists) return Promise.resolve();
  return src(srcPath).pipe(renameTo("LICENSE-PICO.md")).pipe(dest("dist"));
}

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

function serve() {
  // WP Admin'i proxy ederek aç (snippet/injection için önerilir)
  const httpsOpt = getHttpsOption();
  const loginUser = process.env.WP_DEV_USER || "";
  const loginPass = process.env.WP_DEV_PASS || "";
  const injectLogin = Boolean(loginUser && loginPass);

  const bsConfig = {
    proxy: "https://wp-dev.local",
    startPath: "/wp-admin/",
    https: httpsOpt,
    open: "local", // https://localhost:<port>/wp-admin/ açılır
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

  // SCSS → CSS akışı ve canlı yenileme
  watch(paths.styles.src, series(styles, copyLicenses));

  // PHP/HTML değişikliklerinde tam yenileme
  watch(["**/*.php", paths.html.src]).on("change", browserSync.reload);
}


exports.styles = styles;
exports["pico:scope"] = picoScope;
exports.watch = series(cleanOut, styles, copyLicenses, serve);
exports.build = series(cleanOut, styles, copyLicenses);
exports.default = series(cleanOut, styles, copyLicenses);
