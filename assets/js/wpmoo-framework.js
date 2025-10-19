/**
 * WPMoo Framework - Admin Options Scripts
 */
(function ($) {
  "use strict";

  $(document).ready(function () {
    // Section navigation
    $(".wpmoo-nav li a").on("click", function (e) {
      e.preventDefault();

      var sectionId = $(this).data("section");

      // Update nav active state
      $(".wpmoo-nav li").removeClass("wpmoo-section-active");
      $(this).parent("li").addClass("wpmoo-section-active");

      // Show corresponding section
      $(".wpmoo-section").removeClass("wpmoo-section-active");
      $('.wpmoo-section[data-section="' + sectionId + '"]').addClass(
        "wpmoo-section-active"
      );

      // Scroll to top of content
      $(".wpmoo-content").scrollTop(0);
    });

    // Search functionality
    var searchTimeout;
    $(".wpmoo-search-input").on("input", function () {
      clearTimeout(searchTimeout);
      var query = $(this).val().toLowerCase();

      searchTimeout = setTimeout(function () {
        if (query.length === 0) {
          // Show all fields
          $(".wpmoo-field").show();
          $(".wpmoo-section-title").show();
          return;
        }

        // Search through fields
        $(".wpmoo-field").each(function () {
          var $field = $(this);
          var label = $field.find(".wpmoo-title h4").text().toLowerCase();
          var description = $field
            .find(".wpmoo-subtitle-text")
            .text()
            .toLowerCase();

          if (label.includes(query) || description.includes(query)) {
            $field.show();
          } else {
            $field.hide();
          }
        });

        // Hide section titles if no visible fields
        $(".wpmoo-section").each(function () {
          var $section = $(this);
          var visibleFields = $section.find(".wpmoo-field:visible").length;

          if (visibleFields === 0) {
            $section.find(".wpmoo-section-title").hide();
          } else {
            $section.find(".wpmoo-section-title").show();
            $section.addClass("wpmoo-section-active");
          }
        });
      }, 300);
    });

    // Save button loading state
    $("#wpmoo-options-form").on("submit", function () {
      var $saveButtons = $(".wpmoo-save");
      $saveButtons.prop("disabled", true).addClass("disabled");

      // Re-enable after a delay (in case of errors)
      setTimeout(function () {
        $saveButtons.prop("disabled", false).removeClass("disabled");
      }, 3000);
    });

    // Clear search on escape
    $(document).on("keydown", function (e) {
      if (e.key === "Escape" && $(".wpmoo-search-input").is(":focus")) {
        $(".wpmoo-search-input").val("").trigger("input");
      }
    });
  });
})(jQuery);
