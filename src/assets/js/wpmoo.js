/*!
 * WPMoo Framework – Admin panel bootstrap + module loader.
 * Each behaviour lives under resources/js/wpmoo/* and is injected here at build time.
 * @license GPL-2.0-or-later
 * @link https://github.com/wpmoo/wpmoo
 */
/*!
 * WPMoo Framework – Accordion fallback.
 * Keeps pico <details> accordions in sync and enforces aria state when themes interfere.
 * @license GPL-2.0-or-later
 * @link https://github.com/wpmoo/wpmoo
 */
(function (root) {
  "use strict";

  root.WPMooModuleRegistry = root.WPMooModuleRegistry || [];

  root.WPMooModuleRegistry.push(function (ctx) {
    var $ = ctx.$;
    var $document = $(ctx.document);

    function syncSummaryState($details) {
      if ( !$details || !$details.length ) {
        return;
      }

      var $summary = $details.children(".wpmoo-accordion__summary");
      var $content = $details.children(".wpmoo-accordion__content");
      var isOpen = !!$details.prop("open");

      if ($summary.length) {
        $summary.attr("aria-expanded", isOpen ? "true" : "false");
      }

      if ($content && $content.length) {
        if (isOpen) {
          $content.stop(true, true).slideDown(180).css("display", "block").attr("aria-hidden", "false");
        } else {
          $content.stop(true, true).slideUp(180).css("display", "none").attr("aria-hidden", "true");
        }
      }
    }

    $(".wpmoo-accordion details.wpmoo-accordion__item").each(function () {
      syncSummaryState($(this));
    });

    $document.on("click", ".wpmoo-accordion__summary", function (event) {
      var $details = $(this).closest("details.wpmoo-accordion__item");
      if ( ! $details.length ) {
        return;
      }
      event.preventDefault();
      $details.prop("open", !$details.prop("open"));
      syncSummaryState($details);
    });

    $document.on("keydown", ".wpmoo-accordion__summary", function (event) {
      if ( event.key === "Enter" || event.key === " " || event.key === "Spacebar" ) {
        var $details = $(this).closest("details.wpmoo-accordion__item");
        if ( ! $details.length ) {
          return;
        }
        event.preventDefault();
        $details.prop("open", !$details.prop("open"));
        syncSummaryState($details);
      }
    });
  });
})(window);


/*!
 * WPMoo Framework – Contextual field help popovers.
 * Renders accessible tooltip dialogs for .wpmoo-field-help triggers.
 * @license GPL-2.0-or-later
 * @link https://github.com/wpmoo/wpmoo
 */
(function (root) {
  "use strict";

  root.WPMooModuleRegistry = root.WPMooModuleRegistry || [];

  root.WPMooModuleRegistry.push(function (ctx) {
    var $ = ctx.$;
    var $document = $(ctx.document);
    var $window = $(ctx.window);
    var HELP_SELECTOR = ".wpmoo-field-help";
    var helpPopoverId = "wpmoo-help-popover";
    var $activeButton = null;
    var $popover = null;
    var decoder = ctx.document.createElement("textarea");

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

    function closePopover() {
      if (!$activeButton) {
        return;
      }

      var $panel = ensurePopover();
      $panel.attr("aria-hidden", "true").removeClass("is-visible");
      $panel.find(".wpmoo-help-popover__content").empty();
      $activeButton.attr("aria-expanded", "false");
      $activeButton = null;
    }

    function openPopover($button) {
      var content = $button.data("help") || $button.attr("data-help");
      if (!content) {
        return;
      }

      content = decodeEntities(content);

      var $panel = ensurePopover();
      $panel.find(".wpmoo-help-popover__content").html(escapeHtml(content));
      positionPopover($button, $panel);
      $panel.attr("aria-hidden", "false").addClass("is-visible");
      $button.attr("aria-expanded", "true");
      $activeButton = $button;
    }

    function positionPopover($button, $panel) {
      if (!$button || !$button.length || !$panel || !$panel.length) {
        return;
      }

      var offset = $button.offset();
      var height = $button.outerHeight();
      var width = $button.outerWidth();
      var panelWidth = $panel.outerWidth();
      var scrollTop = $window.scrollTop() || 0;
      var top = offset.top + height + 12;
      var left = offset.left + width / 2 - panelWidth / 2;

      $panel.css({
        top: top,
        left: Math.max(12, left),
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
  });
})(window);


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


/*!
 * WPMoo Framework – Panel/tabs controller.
 * Manages tab ↔ accordion layouts, persistence and aria states for grouped sections.
 * @license GPL-2.0-or-later
 * @link https://github.com/wpmoo/wpmoo
 */
(function (root) {
  "use strict";

  root.WPMooModuleRegistry = root.WPMooModuleRegistry || [];

  root.WPMooModuleRegistry.push(function (ctx) {
    var $ = ctx.$;
    var canStore = ctx.canStore;
    var windowObj = ctx.window;
    var $panels = $("[data-wpmoo-panel]");

    if ( ! $panels.length ) {
      return;
    }

    $panels.each(function () {
      var $panel = $(this);
      var panelId = $panel.data("panel-id") || $panel.attr("id") || "";
      var storageKey = panelId ? "wpmoo_panel_active_" + panelId : "";
      var $tabs = $panel.find("[data-panel-tab]");
      var $sections = $panel.find("[data-panel-section]");
      var initialTarget = $panel.data("panel-active") || "";
      var $form = $panel.closest("form");
      var $hidden = null;
      var activeInputName = panelId ? "_wpmoo_active_panel[" + panelId + "]" : "_wpmoo_active_panel";

      if ($form.length) {
        $hidden = $form.find('input[type="hidden"][name="' + activeInputName + '"]').first();
        if ( ! $hidden.length ) {
          $hidden = $("<input>", { type: "hidden", name: activeInputName }).appendTo($form);
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
        if ( ! $targetTab.length && $tabs.length ) {
          $targetTab = $tabs.first();
          target = $targetTab.data("panel-tab");
        }

        var $targetSection = $sections.filter('[data-panel-section="' + target + '"]');
        if ( ! $targetSection.length && $sections.length ) {
          $targetSection = $sections.first();
          target = $targetSection.data("panel-section");
        }

        if ($tabs.length) {
          $tabs.removeClass("is-active").attr("aria-selected", "false");
          if ($targetTab.length) {
            $targetTab.addClass("is-active").attr("aria-selected", "true");
          }
        }

        var $switches = $panel.find("[data-panel-switch]");

        if (isAccordionMode && allowMulti) {
          if ($targetSection.length) {
            $targetSection.addClass("is-active").attr("aria-hidden", "false");
          }

          $switches.each(function () {
            var $btn = $(this);
            var sectionId = $btn.data("panel-switch");
            var $section = $sections.filter('[data-panel-section="' + sectionId + '"]');
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

          if ( ! isAccordionMode ) {
            $sections.each(function () {
              var $section = $(this);
              var $body = $section.find(".wpmoo-panel__section-body");
              if ( ! $body.length ) {
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

        if ( ! skipStore && canStore && storageKey ) {
          try {
            windowObj.localStorage.setItem(storageKey, target);
          } catch (error) {
            // Ignore storage failures.
          }
        }
      }

      if ( ! $tabs.length ) {
        $sections.addClass("is-active").attr("aria-hidden", "false");
        if ($hidden && $sections.length) {
          $hidden.val($sections.first().data("panel-section"));
        }
        return;
      }

      var stored = "";
      if (canStore && storageKey) {
        try {
          stored = windowObj.localStorage.getItem(storageKey) || "";
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

      var currentTarget = $tabs.filter(".is-active").first().data("panel-tab");
      if ( ! currentTarget ) {
        currentTarget = $sections.filter(".is-active").first().data("panel-section");
      }

      if ( ! initialTarget && currentTarget ) {
        initialTarget = currentTarget;
      }

      if ($hidden && currentTarget) {
        $hidden.val(currentTarget);
      }

      if ( initialTarget && initialTarget !== currentTarget ) {
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

      $panel.on("toggle", "details[data-panel-section]", function () {
        var $details = $(this);
        var allowMulti =
          $panel.data("panel-multi") === 1 ||
          $panel.data("panel-multi") === "1" ||
          $panel.data("panel-multi") === true;

        if ( ! allowMulti && this.open ) {
          $details.siblings("details[data-panel-section]").prop("open", false);
        }

        var targetId = this.open ? ($details.attr("id") || "") : "";
        if ($hidden && targetId) {
          $hidden.val(targetId);
        }
        if (canStore && panelId) {
          try {
            windowObj.localStorage.setItem("wpmoo_panel_active_" + panelId, targetId);
          } catch (error) {}
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
          if ( ! allowMulti ) {
            var target = $hidden ? $hidden.val() : "";

            if ( ! target ) {
              var $activeDesktop = $sections.filter(".is-active").first();
              if ($activeDesktop.length) {
                target = $activeDesktop.data("panel-section");
              }
            }

            if ( ! target && $sections.length ) {
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
            var $section = $sections.filter('[data-panel-section="' + sectionId + '"]');
            var isOpen = $section.hasClass("is-active");
            $btn.toggleClass("is-active", isOpen);
            $btn.attr("aria-expanded", isOpen ? "true" : "false");
          });
        } else {
          var targetDesktop = $hidden ? $hidden.val() : "";

          if ( ! targetDesktop ) {
            var $activeSection = $sections.filter(".is-active").last();
            if ($activeSection.length) {
              targetDesktop = $activeSection.data("panel-section");
            }
          }

          if ( ! targetDesktop && $sections.length ) {
            targetDesktop = $sections.first().data("panel-section");
          }

          activate(targetDesktop, true);
          $sections.find(".wpmoo-panel__section-body").show();
        }
      }

      handleAccordionState();
      $(ctx.window).on("resize", handleAccordionState);

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
            var $remaining = $sections.filter(".is-active").not($section);
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
          var $bodyAccordion = $section.find(".wpmoo-panel__section-body");
          if ($bodyAccordion.length) {
            $bodyAccordion.stop(true, true).slideDown(180);
          }
        }
      });
    });
  });
})(window);


/*!
 * WPMoo Framework – Repeatable field controls.
 * Handles add/clone/remove/sort actions and numbering for .wpmoo-repeat blocks.
 * @license GPL-2.0-or-later
 * @link https://github.com/wpmoo/wpmoo
 */
(function (root) {
  "use strict";

  root.WPMooModuleRegistry = root.WPMooModuleRegistry || [];

  root.WPMooModuleRegistry.push(function (ctx) {
    var $ = ctx.$;
    var $document = $(ctx.document);

    function initRepeatableSorting($root) {
      var $lists = ($root && $root.length ? $root : $document).find(".wpmoo-repeat__items");
      $lists.each(function () {
        var $list = $(this);
        if (typeof $list.sortable === "function") {
          try {
            $list.sortable({
              items: "> .wpmoo-repeat__item",
              handle: ".wpmoo-repeat__handle",
              axis: "y",
              tolerance: "pointer",
              update: function () {
                renumber($list.closest(".wpmoo-repeat"));
              },
            });
          } catch (error) {
            // Silently ignore sortable errors (jQuery UI missing, etc.)
          }
        }
      });
    }

    function renumber($wrap) {
      if (!$wrap || !$wrap.length) {
        return;
      }

      var base = $wrap.data("repeat-label") || "";
      var $items = $wrap.find(".wpmoo-repeat__item");

      $items.each(function (index) {
        var $item = $(this);
        var number = index + 1;
        $item.attr("data-repeat-index", number);
        var title = base ? number + ". " + base : "#" + number;
        $item.find(".wpmoo-repeat__title").text(title);
        $item.find("[data-repeat-up]").prop("disabled", index === 0);
        $item.find("[data-repeat-down]").prop("disabled", index === $items.length - 1);
      });
    }

    function resetFields($scope) {
      $scope.find("input, select, textarea").each(function () {
        var $field = $(this);
        if ($field.is(":checkbox") || $field.is(":radio")) {
          $field.prop("checked", false);
        } else {
          $field.val("");
        }
      });
    }

    $document.on("click", "[data-repeat-add]", function () {
      var $wrap = $(this).closest(".wpmoo-repeat");
      if (!$wrap.length) {
        return;
      }

      var $items = $wrap.find(".wpmoo-repeat__items");
      if (!$items.length) {
        return;
      }

      var max = parseInt($wrap.data("repeat-max") || 0, 10) || 0;
      var count = $items.children().length;
      if (max > 0 && count >= max) {
        return;
      }

      var $last = $items.children().last();
      var $clone = $last.clone(true, true);
      resetFields($clone);
      $items.append($clone);

      var $first = $clone.find("input, select, textarea").first();
      if ($first.length) {
        $first.trigger("focus");
      }

      initRepeatableSorting($wrap);
      renumber($wrap);
    });

    $document.on("click", "[data-repeat-remove]", function () {
      var $item = $(this).closest(".wpmoo-repeat__item");
      var $wrap = $(this).closest(".wpmoo-repeat");
      var $itemsWrap = $wrap.find(".wpmoo-repeat__items");
      var min = parseInt($wrap.data("repeat-min") || 0, 10) || 0;
      var count = $itemsWrap.children(".wpmoo-repeat__item").length;

      if (count <= Math.max(min, 1)) {
        resetFields($item);
        return;
      }

      $item.remove();
      renumber($wrap);
    });

    $document.on("click", "[data-repeat-clone]", function () {
      var $item = $(this).closest(".wpmoo-repeat__item");
      var $wrap = $(this).closest(".wpmoo-repeat");
      if (!$item.length || !$wrap.length) {
        return;
      }

      var $itemsWrap = $wrap.find(".wpmoo-repeat__items");
      if (!$itemsWrap.length) {
        return;
      }

      var max = parseInt($wrap.data("repeat-max") || 0, 10) || 0;
      var count = $itemsWrap.children(".wpmoo-repeat__item").length;
      if (max > 0 && count >= max) {
        return;
      }

      var $clone = $item.clone(true, true);
      resetFields($clone);
      $clone.insertAfter($item);

      var $first = $clone.find("input, select, textarea").first();
      if ($first.length) {
        $first.trigger("focus");
      }

      initRepeatableSorting($wrap);
      renumber($wrap);
    });

    $document.on("click", "[data-repeat-up]", function () {
      var $item = $(this).closest(".wpmoo-repeat__item");
      var $wrap = $(this).closest(".wpmoo-repeat");
      var $prev = $item.prev(".wpmoo-repeat__item");
      if ($prev.length) {
        $item.insertBefore($prev);
        renumber($wrap);
      }
    });

    $document.on("click", "[data-repeat-down]", function () {
      var $item = $(this).closest(".wpmoo-repeat__item");
      var $wrap = $(this).closest(".wpmoo-repeat");
      var $next = $item.next(".wpmoo-repeat__item");
      if ($next.length) {
        $item.insertAfter($next);
        renumber($wrap);
      }
    });

    initRepeatableSorting($document);
    $(".wpmoo-repeat").each(function () {
      renumber($(this));
    });
  });
})(window);


/*!
 * WPMoo Framework – Smooth scroll helper for sidebar/menu anchor jumps.
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


/*!
 * WPMoo Framework – Vertical tabs controller.
 * Handles focus management and visibility for Tabs::vertical() layouts.
 * @license GPL-2.0-or-later
 * @link https://github.com/wpmoo/wpmoo
 */
(function (root) {
  "use strict";

  root.WPMooModuleRegistry = root.WPMooModuleRegistry || [];

  root.WPMooModuleRegistry.push(function (ctx) {
    var $ = ctx.$;
    var SELECTOR = '[data-wpmoo-tabs][data-tabs-orientation="vertical"]';

    $(SELECTOR).each(function () {
      var $component = $(this);
      var $buttons = $component.find(".wpmoo-tabs__nav [data-tabs-target]");
      var $panels = $component.find("[data-tabs-panel]");

      if (!$buttons.length || !$panels.length) {
        return;
      }

      function activate(target, focusButton) {
        if (!target) {
          target = $buttons.first().data("tabs-target");
        }

        var $button = $buttons.filter('[data-tabs-target="' + target + '"]');
        var $panel = $panels.filter('[data-tabs-panel="' + target + '"]');

        if (!$button.length || !$panel.length) {
          $button = $buttons.first();
          target = $button.data("tabs-target");
          $panel = $panels.filter('[data-tabs-panel="' + target + '"]');
        }

        $buttons
          .removeClass("is-active")
          .attr("aria-selected", "false")
          .attr("tabindex", "-1");

        $panels.removeClass("is-active").attr("aria-hidden", "true");

        $button.addClass("is-active").attr("aria-selected", "true").attr("tabindex", "0");
        $panel.addClass("is-active").attr("aria-hidden", "false");

        if (focusButton) {
          $button.trigger("focus");
        }
      }

      function focusRelative(offset) {
        var currentIndex = $buttons.index($buttons.filter(".is-active").first());
        if (currentIndex < 0) {
          currentIndex = 0;
        }

        var nextIndex = (currentIndex + offset + $buttons.length) % $buttons.length;
        var $target = $buttons.eq(nextIndex);
        activate($target.data("tabs-target"), true);
      }

      $component.addClass("is-ready");
      activate($buttons.first().data("tabs-target"));

      $buttons.on("click", function (event) {
        event.preventDefault();
        activate($(this).data("tabs-target"));
      });

      $buttons.on("keydown", function (event) {
        if (event.key === "Enter" || event.key === " " || event.key === "Spacebar") {
          event.preventDefault();
          activate($(this).data("tabs-target"));
          return;
        }

        if (event.key === "ArrowDown" || event.key === "ArrowRight") {
          event.preventDefault();
          focusRelative(1);
          return;
        }

        if (event.key === "ArrowUp" || event.key === "ArrowLeft") {
          event.preventDefault();
          focusRelative(-1);
          return;
        }

        if (event.key === "Home") {
          event.preventDefault();
          activate($buttons.first().data("tabs-target"), true);
          return;
        }

        if (event.key === "End") {
          event.preventDefault();
          activate($buttons.last().data("tabs-target"), true);
        }
      });
    });
  });
})(window);


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
