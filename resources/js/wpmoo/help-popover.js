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
