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

      if ($form.length && panelId) {
        $hidden = $form
          .find('input[type="hidden"][name^="_wpmoo_active_panel"]')
          .filter(function () {
            return $(this).data("panel-id") === panelId;
          })
          .first();

        if (!$hidden.length) {
          $hidden = $("<input>", {
            type: "hidden",
            name: "_wpmoo_active_panel[" + panelId + "]",
          })
            .appendTo($form)
            .data("panel-id", panelId);
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

      // Safari submits forms before DOM paint; defer activation to next frame.
      setTimeout(function () {
        activate(initialTarget, true);
      }, 0);

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
})(jQuery);
