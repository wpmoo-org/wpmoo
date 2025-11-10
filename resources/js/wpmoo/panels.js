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
