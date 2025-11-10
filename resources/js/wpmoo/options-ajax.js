/*!
 * WPMoo Framework – Options page AJAX save + toast feedback.
 * Hijacks the options form to post via AJAX, display toasts and persist active panels.
 * @license GPL-2.0-or-later
 * @link https://github.com/wpmoo/wpmoo
 */
(function (root) {
  "use strict";

  root.WPMooModuleRegistry = root.WPMooModuleRegistry || [];

  root.WPMooModuleRegistry.push(function (ctx) {
    var $ = ctx.$;
    var windowObj = ctx.window;
    var optionsConfig = windowObj.wpmooAdminOptions || null;

    if ( ! optionsConfig || ! optionsConfig.menu_slug ) {
      return;
    }

    var $optionsForm = $("#wpmoo-options-form");
    if ( ! $optionsForm.length ) {
      return;
    }

    if (optionsConfig.ajax_save === false || optionsConfig.ajax_save === 0 || optionsConfig.ajax_save === "0") {
      return;
    }

    var $submitButtons = $optionsForm.find('button[type="submit"], input[type="submit"]');
    var toastContainer = $(".wpmoo-toast-container");

    if ( ! toastContainer.length ) {
      toastContainer = $("<div>", { class: "wpmoo-toast-container" });
      $("body").append(toastContainer);
    }

    var allowPersistence =
      optionsConfig.persistTabs === true ||
      optionsConfig.persistTabs === 1 ||
      optionsConfig.persistTabs === "1";

    function getString(key, fallback) {
      if (optionsConfig.strings && Object.prototype.hasOwnProperty.call(optionsConfig.strings, key)) {
        return optionsConfig.strings[key];
      }
      return fallback;
    }

    function showToast(type, message) {
      var toastClass = "wpmoo-toast";
      if (type === "success") {
        toastClass += " wpmoo-toast--success";
      } else if (type === "error") {
        toastClass += " wpmoo-toast--error";
      } else {
        toastClass += " wpmoo-toast--info";
      }

      var $toast = $("<div>", {
        class: toastClass,
        text: message,
      });

      toastContainer.append($toast);

      requestAnimationFrame(function () {
        $toast.addClass("is-visible");
      });

      setTimeout(function () {
        $toast.removeClass("is-visible");
        setTimeout(function () {
          $toast.remove();
        }, 200);
      }, 4000);
    }

    $optionsForm.on("submit", function (event) {
      event.preventDefault();

      var payload = $optionsForm.serializeArray();
      payload.push({ name: "action", value: "wpmoo_save_options" });
      payload.push({ name: "menu_slug", value: optionsConfig.menu_slug });
      payload.push({ name: "nonce", value: optionsConfig.nonce });

      var originalLabels = [];

      $submitButtons.each(function (index, button) {
        var $button = $(button);
        if ($button.is("button")) {
          originalLabels[index] = $button.text();
          $button.text(getString("saving", "Saving…"));
        } else {
          originalLabels[index] = $button.val();
          $button.val(getString("saving", "Saving…"));
        }
      });

      $submitButtons.prop("disabled", true).addClass("disabled");

      $.ajax({
        url: optionsConfig.ajax_url || windowObj.ajaxurl,
        method: "POST",
        dataType: "json",
        data: payload,
      })
        .done(function (response) {
          if (response && response.success) {
            var message =
              (response.data && response.data.message) ||
              getString("saved", "Settings saved.");

            showToast("success", message);

            if (response.data && response.data.activePanel) {
              $optionsForm.find(".wpmoo-active-panel").val(response.data.activePanel);

              if (optionsConfig.menu_slug && response.data.activePanel) {
                var storageKey = "wpmoo_panel_active_wpmoo-options-panel-" + optionsConfig.menu_slug;
                try {
                  windowObj.localStorage.setItem(storageKey, response.data.activePanel);
                  optionsConfig.persistTabs = true;
                  allowPersistence = true;
                  var $panelElement = $('[data-panel-id="wpmoo-options-panel-' + optionsConfig.menu_slug + '"]');
                  $panelElement.data("panel-persist", true).attr("data-panel-persist", "1");
                } catch (error) {
                  // Ignore storage errors.
                }
              }
            }
          } else {
            var errorMessage =
              (response && response.data && response.data.message) ||
              getString("error", "Unable to save settings.");

            showToast("error", errorMessage);
          }
        })
        .fail(function () {
          showToast("error", getString("error", "Unable to save settings."));
          try {
            $optionsForm.off("submit");
            $optionsForm.trigger("submit");
          } catch (error) {}
        })
        .always(function () {
          $submitButtons.prop("disabled", false).removeClass("disabled");

          $submitButtons.each(function (index, button) {
            var $button = $(button);
            if ($button.is("button")) {
              $button.text(originalLabels[index]);
            } else {
              $button.val(originalLabels[index]);
            }
          });
        });
    });
  });
})(window);
