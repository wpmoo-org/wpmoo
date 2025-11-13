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
