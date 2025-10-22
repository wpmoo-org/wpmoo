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

        var isAccordionMode = $panel.find(".wpmoo-panel__tabs").is(":hidden");
        var allowMulti =
          $panel.data("panel-multi") === 1 ||
          $panel.data("panel-multi") === "1" ||
          $panel.data("panel-multi") === true;

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

        var $switches = $panel.find('[data-panel-switch]');

        if (isAccordionMode && allowMulti) {
          if ($targetSection.length) {
            $targetSection.addClass("is-active").attr("aria-hidden", "false");
          }

          $switches.each(function () {
            var $btn = $(this);
            var sectionId = $btn.data("panel-switch");
            var $section = $sections.filter(
              '[data-panel-section="' + sectionId + '"]'
            );
            var isOpen = $section.hasClass("is-active");
            $btn.toggleClass("is-active", isOpen);
            $btn.attr("aria-expanded", isOpen ? "true" : "false");
          });
        } else {
          $switches.each(function () {
            var $btn = $(this);
            var isTarget = $btn.data("panel-switch") === target;
            $btn.toggleClass("is-active", isTarget);
            $btn.attr("aria-expanded", isTarget ? "true" : "false");
          });

          $sections.removeClass("is-active").attr("aria-hidden", "true");
          if ($targetSection.length) {
            $targetSection.addClass("is-active").attr("aria-hidden", "false");
          }

          if (!isAccordionMode) {
            // Ensure hidden inline styles from accordion mode don't stop tab content from showing.
            $sections.each(function () {
              var $section = $(this);
              var $body = $section.find(".wpmoo-panel__section-body");

              if (!$body.length) {
                return;
              }

              if ($section.is($targetSection)) {
                $body.stop(true, true).show();
              } else {
                $body.stop(true, true).hide();
              }
            });
          }
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

      var panelPersistAttr = $panel.data("panel-persist");
      var allowPersistence =
        panelPersistAttr === undefined ||
        panelPersistAttr === true ||
        panelPersistAttr === 1 ||
        panelPersistAttr === "1";

      if (
        allowPersistence &&
        stored &&
        $tabs.filter('[data-panel-tab="' + stored + '"]').length
      ) {
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

      function handleAccordionState() {
        var isAccordion = $panel.find(".wpmoo-panel__tabs").is(":hidden");
        $panel.data("wpmoo-accordion", isAccordion);

        var allowMulti =
          $panel.data("panel-multi") === 1 ||
          $panel.data("panel-multi") === "1" ||
          $panel.data("panel-multi") === true;

        if (isAccordion) {
          if (!allowMulti) {
            var target = $hidden ? $hidden.val() : "";

            if (!target) {
              var $activeDesktop = $sections.filter(".is-active").first();
              if ($activeDesktop.length) {
                target = $activeDesktop.data("panel-section");
              }
            }

            if (!target && $sections.length) {
              target = $sections.first().data("panel-section");
            }

            activate(target, true);
          }

          $sections.each(function () {
            var $section = $(this);
            var $body = $section.find(".wpmoo-panel__section-body");

            if ($section.hasClass("is-active")) {
              $body.show();
            } else {
              $body.hide();
            }
          });

          $panel.find("[data-panel-switch]").each(function () {
            var $btn = $(this);
            var sectionId = $btn.data("panel-switch");
            var $section = $sections.filter(
              '[data-panel-section="' + sectionId + '"]'
            );
            var isOpen = $section.hasClass("is-active");
            $btn.toggleClass("is-active", isOpen);
            $btn.attr("aria-expanded", isOpen ? "true" : "false");
          });
        } else {
          var target = $hidden ? $hidden.val() : "";

          if (!target) {
            var $activeSection = $sections.filter(".is-active").last();
            if ($activeSection.length) {
              target = $activeSection.data("panel-section");
            }
          }

          if (!target && $sections.length) {
            target = $sections.first().data("panel-section");
          }

          activate(target, true);
          $sections.find(".wpmoo-panel__section-body").show();
        }
      }

      handleAccordionState();

      $(window).on("resize", function () {
        handleAccordionState();
      });

      $panel.on("click", "[data-panel-switch]", function (event) {
        event.preventDefault();
        var target = $(this).data("panel-switch");
        var isAccordion = $panel.find(".wpmoo-panel__tabs").is(":hidden");
        var allowMulti =
          $panel.data("panel-multi") === 1 ||
          $panel.data("panel-multi") === "1" ||
          $panel.data("panel-multi") === true;
        var $section = $panel.find('[data-panel-section="' + target + '"]');

        if (isAccordion && allowMulti && $section.hasClass("is-active")) {
          $section.removeClass("is-active").attr("aria-hidden", "true");
          $(this).removeClass("is-active").attr("aria-expanded", "false");

          var $body = $section.find(".wpmoo-panel__section-body");
          if ($body.length) {
            $body.stop(true, true).slideUp(180);
          }

          if ($hidden && $hidden.val() === target) {
            var $remaining = $sections
              .filter(".is-active")
              .not($section);

            if ($remaining.length) {
              $hidden.val($remaining.last().data("panel-section"));
            } else {
              $hidden.val("");
            }
          }

          return;
        }

        activate(target);

        if (isAccordion && $section.length) {
          var $body = $section.find(".wpmoo-panel__section-body");

          if ($body.length) {
            $body.stop(true, true).slideDown(180);
          }
        }
      });
    });
  });

  (function registerFieldHelp() {
    var HELP_SELECTOR = ".wpmoo-field-help";
    var helpPopoverId = "wpmoo-help-popover";
    var $activeButton = null;
    var $popover = null;
    var $document = $(document);
    var $window = $(window);
    var decoder = document.createElement("textarea");

    function decodeEntities(value) {
      if (!value) {
        return "";
      }

      decoder.innerHTML = value;
      return decoder.value;
    }

    function escapeHtml(value) {
      return $("<div>").text(value || "").html();
    }

    function ensurePopover() {
      if ($popover && $popover.length) {
        return $popover;
      }

      $popover = $("<div>", {
        id: helpPopoverId,
        class: "wpmoo-help-popover",
        role: "dialog",
        "aria-hidden": "true",
      })
        .append('<div class="wpmoo-help-popover__content"></div>')
        .append('<div class="wpmoo-help-popover__arrow"></div>');

      $("body").append($popover);

      return $popover;
    }

    function getPopoverContent($button) {
      var htmlAttr = $button.attr("data-help-html");
      if (htmlAttr) {
        return decodeEntities(htmlAttr);
      }

      var text = $button.attr("data-help-text") || $button.attr("data-tooltip") || "";
      if (text) {
        return "<p>" + escapeHtml(text) + "</p>";
      }

      return "";
    }

    function positionPopover($button, popover) {
      if (!popover || !popover.length || !$button || !$button.length) {
        return;
      }

      var buttonNode = $button[0];
      if (!buttonNode.getBoundingClientRect) {
        return;
      }

      var rect = buttonNode.getBoundingClientRect();
      var gap = 12;
      var scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
      var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft || 0;

      popover.css({ top: 0, left: 0, transform: "none" });
      var popoverWidth = popover.outerWidth();
      var popoverHeight = popover.outerHeight();

      var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
      var availableRight = viewportWidth - rect.right;
      var availableLeft = rect.left;
      var placement = "right";

      if (availableRight < popoverWidth + gap && availableLeft > availableRight) {
        placement = "left";
      }

      var top = scrollTop + rect.top + rect.height / 2 - popoverHeight / 2;
      var minTop = scrollTop + 12;
      var maxTop = scrollTop + (window.innerHeight || document.documentElement.clientHeight || popoverHeight) - popoverHeight - 12;
      top = Math.max(minTop, Math.min(top, maxTop));

      var left =
        placement === "right"
          ? scrollLeft + rect.right + gap
          : scrollLeft + rect.left - popoverWidth - gap;

      popover
        .attr("data-side", placement)
        .css({
          top: Math.round(top) + "px",
          left: Math.round(left) + "px",
        });
    }

    function closePopover() {
      if (!$activeButton) {
        return;
      }

      var popover = ensurePopover();
      popover.removeClass("is-visible").attr("aria-hidden", "true").attr("data-side", "");
      popover.find(".wpmoo-help-popover__content").empty();

      $activeButton.removeClass("is-active").attr("aria-expanded", "false");
      $activeButton = null;
    }

    function openPopover($button) {
      if (!$button || !$button.length) {
        return;
      }

      var content = getPopoverContent($button);
      if (!content) {
        closePopover();
        return;
      }

      if ($activeButton && $button.is($activeButton)) {
        closePopover();
        return;
      }

      closePopover();

      var popover = ensurePopover();
      popover.find(".wpmoo-help-popover__content").html(content);
      popover.attr("aria-hidden", "false");

      $activeButton = $button;
      $button.addClass("is-active").attr("aria-expanded", "true").attr("aria-controls", helpPopoverId);

      positionPopover($button, popover);
      requestAnimationFrame(function () {
        popover.addClass("is-visible");
      });
    }

    $document.on("click", HELP_SELECTOR, function (event) {
      event.preventDefault();
      openPopover($(this));
    });

    $document.on("keydown", HELP_SELECTOR, function (event) {
      if (event.key === "Enter" || event.key === " " || event.key === "Spacebar") {
        event.preventDefault();
        openPopover($(this));
      } else if (event.key === "Escape") {
        closePopover();
      }
    });

    $document.on("click", function (event) {
      if (!$activeButton) {
        return;
      }

      var $target = $(event.target);
      if (
        !$target.closest(HELP_SELECTOR).length &&
        !$target.closest(".wpmoo-help-popover").length
      ) {
        closePopover();
      }
    });

    $document.on("keydown", function (event) {
      if (event.key === "Escape") {
        closePopover();
      }
    });

    $window.on("resize scroll", function () {
      if (!$activeButton) {
        return;
      }

      positionPopover($activeButton, ensurePopover());
    });
  })();

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

      var allowPersistence =
        optionsConfig.persistTabs === true ||
        optionsConfig.persistTabs === 1 ||
        optionsConfig.persistTabs === "1";

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
                    optionsConfig.persistTabs = true;
                    allowPersistence = true;
                    var $panelElement = $(
                      '[data-panel-id="wpmoo-options-panel-' +
                        optionsConfig.menuSlug +
                        '"]'
                    );
                    $panelElement
                      .data('panel-persist', true)
                      .attr('data-panel-persist', '1');
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
