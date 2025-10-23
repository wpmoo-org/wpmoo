const { src, dest, watch, series } = require("gulp");
const { Transform } = require("stream");
const path = require("path");
const { fileURLToPath } = require("url");
const sass = require("sass");
const cleanCSS = require("gulp-clean-css");
const sourcemaps = require("gulp-sourcemaps");

const paths = {
  styles: {
    entry: "assets/scss/wpmoo.scss",
    src: "assets/scss/**/*.scss",
    dest: "assets/css",
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
  return src(paths.styles.entry)
    .pipe(sourcemaps.init())
    .pipe(
      compileSass({
        style: "expanded",
      })
    )
    .pipe(cleanCSS())
    .pipe(sourcemaps.write("."))
    .pipe(dest(paths.styles.dest));
}

function watchStyles() {
  watch(paths.styles.src, styles);
}

exports.styles = styles;
exports.watch = series(styles, watchStyles);
exports.build = styles;
exports.default = styles;
