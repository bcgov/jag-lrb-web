(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.views_year_filter = {
    attach: function (context, settings) {
      // In the normal dates operator is select.
      if ($('[name="options[operator]"]').is('select')) {
        $('[name="options[operator]"]').on('change', function () {
          if ($(this).val() !== '=') {
            $('[value="date_year"]').attr('disabled', true);
            $('[value="date"]').attr('checked', 'checked');
          }
          else {
            $('[value="date_year"]').attr('disabled', false);
          }
        });
      }
      else {
        // In timestamp dates like created and changed operator is radio inputs.
        $('[name="options[operator]"]').on('click', function () {
          if ($(this).is(':checked')) {
            if ($(this).val() !== '=') {
              $('[value="date_year"]').attr('disabled', true);
              $('[value="date"]').attr('checked', 'checked');
            }
            else {
              $('[value="date_year"]').attr('disabled', false);
            }
          }
          $(this).on('change', function () {
            if ($(this).val() !== '=') {
              $('[value="date_year"]').attr('disabled', true);
              $('[value="date"]').attr('checked', 'checked');
            }
            else {
              $('[value="date_year"]').attr('disabled', false);
            }
          });
        });
      }
    }
  };
})(jQuery, Drupal);
