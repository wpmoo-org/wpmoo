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
