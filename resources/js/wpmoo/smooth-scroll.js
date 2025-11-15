/*!
 * WPMoo Framework – Smooth scroll helper for anchor jumps.
 * Intercepts in-page links inside .wpmoo layout and scrolls smoothly.
 * @license GPL-2.0-or-later
 * @link https://github.com/wpmoo/wpmoo
 */
(function (root) {
  "use strict";

  root.WPMooModuleRegistry = root.WPMooModuleRegistry || [];

  root.WPMooModuleRegistry.push(function (ctx) {
    var documentRef = ctx.document;
    var rootEl = documentRef.querySelector(".wpmoo");

    if (!rootEl) {
      return;
    }

    rootEl.addEventListener(
      "click",
      function (event) {
        var anchor = event.target.closest('a[href^="#"]');
        if (!anchor) {
          return;
        }

        var href = anchor.getAttribute("href");
        if (!href || href.charAt(0) !== "#" || href.length <= 1) {
          return;
        }

        var target = rootEl.querySelector(href);
        if (!target) {
          return;
        }

        event.preventDefault();
        target.scrollIntoView({ behavior: "smooth", block: "start" });

        if (ctx.window.history && ctx.window.history.replaceState) {
          ctx.window.history.replaceState(null, "", href);
        } else {
          ctx.window.location.hash = href.substring(1);
        }
      },
      true
    );
  });
})(window);
