/**
 * WPMoo Framework - Admin Panel Scripts
 */
(function ($) {
  "use strict";

  $(document).ready(function () {
    $('[data-wpmoo-panel]').each(function () {
      var $panel = $(this);
      var $tabs = $panel.find('[data-panel-tab]');
      var $sections = $panel.find('[data-panel-section]');

      if ($tabs.length === 0) {
        $sections.addClass('is-active').attr('aria-hidden', 'false');
        return;
      }

      $tabs.on('click', function (event) {
        event.preventDefault();

        var $tab = $(this);
        var target = $tab.data('panel-tab');
        if (!target) {
          return;
        }

        $tabs.removeClass('is-active');
        $tab.addClass('is-active');

        $sections.removeClass('is-active').attr('aria-hidden', 'true');

        var $target = $sections.filter('[data-panel-section="' + target + '"]');
        if ($target.length) {
          $target.addClass('is-active').attr('aria-hidden', 'false');
          $panel.trigger('wpmoo.panel.change', [target, $target]);
        }
      });
    });
  });
})(jQuery);
