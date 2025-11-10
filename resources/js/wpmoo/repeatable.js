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
