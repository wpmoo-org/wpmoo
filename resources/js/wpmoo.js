/*!
 * WPMoo Framework – Admin panel bootstrap + module loader.
 * Each behaviour lives under resources/js/wpmoo/* and is injected here at build time.
 * @license GPL-2.0-or-later
 * @link https://github.com/wpmoo/wpmoo
 */
/* @wpmoo-modules */

(function (windowObj, documentObj, $) {
  "use strict";

  windowObj.WPMooModuleRegistry = windowObj.WPMooModuleRegistry || [];

  function storageAvailable(type) {
    try {
      var storage = windowObj[type];
      var testKey = "__wpmoo_panel_test__";
      storage.setItem(testKey, testKey);
      storage.removeItem(testKey);
      return true;
    } catch (error) {
      return false;
    }
  }

  $(documentObj).ready(function () {
    var context = {
      window: windowObj,
      document: documentObj,
      $: $,
      storageAvailable: storageAvailable,
      canStore: storageAvailable("localStorage"),
    };

    (windowObj.WPMooModuleRegistry || []).forEach(function (initializer) {
      if (typeof initializer !== "function") {
        return;
      }

      try {
        initializer(context);
      } catch (error) {
        if (windowObj.console && typeof windowObj.console.error === "function") {
          windowObj.console.error("WPMoo module error:", error);
        }
      }
    });
  });
})(window, document, jQuery);
