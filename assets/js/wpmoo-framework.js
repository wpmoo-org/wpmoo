/**
 * WPMoo Framework - Admin Panel Scripts
 */
(function ($) {
  "use strict";

  function storageAvailable(type) {
    try {
      var storage = window[type];
      var testKey = "__wpmoo_panel_test__";
      storage.setItem(testKey, testKey);
      storage.removeItem(testKey);
      return true;
    } catch (error) {
      return false;
    }
  }

  $(document).ready(function () {
    var canStore = storageAvailable("localStorage");

    $("[data-wpmoo-panel]").each(function () {
      var $panel = $(this);
      var panelId = $panel.data("panel-id") || $panel.attr("id") || "";
      var storageKey = panelId ? "wpmoo_panel_active_" + panelId : "";
      var $tabs = $panel.find("[data-panel-tab]");
      var $sections = $panel.find("[data-panel-section]");
      var initialTarget = $panel.data("panel-active") || "";
      var $form = $panel.closest("form");
      var $hidden = null;
      var activeInputName = panelId
        ? "_wpmoo_active_panel[" + panelId + "]"
        : "_wpmoo_active_panel";

      if ($form.length) {
        $hidden = $form
          .find('input[type="hidden"][name="' + activeInputName + '"]')
          .first();

        if (!$hidden.length) {
          $hidden = $("<input>", {
            type: "hidden",
            name: activeInputName,
          }).appendTo($form);
        }

        if (panelId) {
          $hidden.data("panel-id", panelId);
        }
      }

      function activate(target, skipStore) {
        if (!target) {
          if ($tabs.length) {
            target = $tabs.first().data("panel-tab");
          } else if ($sections.length) {
            target = $sections.first().data("panel-section");
          } else {
            return;
          }
        }

        var $targetTab = $tabs.filter('[data-panel-tab="' + target + '"]');
        if (!$targetTab.length && $tabs.length) {
          $targetTab = $tabs.first();
          target = $targetTab.data("panel-tab");
        }

        var $targetSection = $sections.filter(
          '[data-panel-section="' + target + '"]'
        );
        if (!$targetSection.length && $sections.length) {
          $targetSection = $sections.first();
          target = $targetSection.data("panel-section");
        }

        if ($tabs.length) {
          $tabs.removeClass("is-active").attr("aria-selected", "false");
          if ($targetTab.length) {
            $targetTab.addClass("is-active").attr("aria-selected", "true");
          }
        }

        $sections.removeClass("is-active").attr("aria-hidden", "true");
        if ($targetSection.length) {
          $targetSection.addClass("is-active").attr("aria-hidden", "false");
        }

        if ($hidden) {
          $hidden.val(target);
        }

        if (!skipStore && canStore && storageKey) {
          try {
            window.localStorage.setItem(storageKey, target);
          } catch (error) {
            // Ignore storage failures.
          }
        }
      }

      if (!$tabs.length) {
        $sections.addClass("is-active").attr("aria-hidden", "false");
        if ($hidden && $sections.length) {
          $hidden.val($sections.first().data("panel-section"));
        }
        return;
      }

      var stored = "";
      if (canStore && storageKey) {
        try {
          stored = window.localStorage.getItem(storageKey) || "";
        } catch (error) {
          stored = "";
        }
      }

      if (stored && $tabs.filter('[data-panel-tab="' + stored + '"]').length) {
        initialTarget = stored;
      }

      var currentTarget = $tabs.filter('.is-active').first().data('panel-tab');
      if (!currentTarget) {
        currentTarget = $sections
          .filter('.is-active')
          .first()
          .data('panel-section');
      }

      if (!initialTarget && currentTarget) {
        initialTarget = currentTarget;
      }

      if ($hidden && currentTarget) {
        $hidden.val(currentTarget);
      }

      if (initialTarget && initialTarget !== currentTarget) {
        setTimeout(function () {
          activate(initialTarget, true);
        }, 0);
      }

      $tabs.on("click", function (event) {
        event.preventDefault();
        activate($(this).data("panel-tab"));
      });

      $tabs.on("keydown", function (event) {
        if (event.key === "Enter" || event.key === " " || event.key === "Spacebar") {
          event.preventDefault();
          activate($(this).data("panel-tab"));
        }
      });
    });
  });

  var optionsConfig = window.wpmooAdminOptions || null;

  if (optionsConfig && optionsConfig.menuSlug) {
    var $optionsForm = $("#wpmoo-options-form");

    if ($optionsForm.length) {
      var $submitButtons = $optionsForm.find(
        'button[type="submit"], input[type="submit"]'
      );

      var toastContainer = $(".wpmoo-toast-container");

      if (!toastContainer.length) {
        toastContainer = $("<div>", { class: "wpmoo-toast-container" });
        $("body").append(toastContainer);
      }

      function getString(key, fallback) {
        if (
          optionsConfig.strings &&
          Object.prototype.hasOwnProperty.call(optionsConfig.strings, key)
        ) {
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
        payload.push({ name: "menu_slug", value: optionsConfig.menuSlug });
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
          url: optionsConfig.ajaxUrl || window.ajaxurl,
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
                $optionsForm
                  .find('.wpmoo-active-panel')
                  .val(response.data.activePanel);

                if (optionsConfig.menuSlug && response.data.activePanel) {
                  var storageKey =
                    "wpmoo_panel_active_" +
                    "wpmoo-options-panel-" +
                    optionsConfig.menuSlug;
                  try {
                    window.localStorage.setItem(
                      storageKey,
                      response.data.activePanel
                    );
                  } catch (error) {}
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
    }
  }
})(jQuery);
