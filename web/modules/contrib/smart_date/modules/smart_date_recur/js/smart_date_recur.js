(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.smartDateRecur = {
    attach: function(context, settings) {
      // Manipulate the labels for BYDAY checkboxes.
      $('.smartdate--widget .byday-checkboxes label').each(function () {
        $(this).prop('title', $(this).text());
        $(this).prop('tabindex', '0');
        // Check the input on spacebar or return.
        $(this).keydown(function (event) {
          if (event.which == 13 || event.which == 32) {
            $(this).siblings('input').click();
            event.preventDefault();
          }
        });
      });

      $('.smartdate--widget .allday').on('change', function () {
        toggleMinutesHours(this);
      });

      // Manipulate the labels for BYHOUR and BYMINUTE checkboxes.
      $('.smart-date--minutes input, .smart-date--hours input').each(function(){
        $(this).prop('tabindex', '0');
      });

      // special handler for duration updates
      $('.smartdate--widget select.field-duration').on('change', function() {
        durationToMinutes(this);
      });

      $('.smartdate--widget select.recur-repeat').once('set-freq').each(function () {
        setDataFreq(this);
      }).on('change', function () {
        updateInterval(this);
      });

      $('.smartdate--widget .time-end').on('change', function () {
        durationToMinutes(this);
      });

      function durationToMinutes(element) {
        var wrapper = $(element).parents('fieldset');
        var freq = wrapper.find('.recur-repeat');
        if (freq.val() !== 'MINUTELY') {
          // The rest only needed for Minutes.
          return;
        }
        var duration_select = wrapper.find('select.field-duration');
        var duration_val = duration_select.val();
        if (duration_val === 'custom') {
          duration_val = parseInt(duration_select.prop('data-duration'));
        }
        var interval = wrapper.find('.field-interval');
        interval.val(duration_val);
      }

      function updateInterval(element) {
        var wrapper = $(element).parents('fieldset');
        var freq = wrapper.find('.recur-repeat');
        if (freq.val() === 'MINUTELY') {
          // When changeing to minutes, set to the current duration.
          durationToMinutes(element);
        }
        else if (freq.prop('data-freq') === 'MINUTELY') {
          // Only reset if changing from minutes.
          var interval = wrapper.find('.field-interval');
          interval.val('');
        }
        freq.prop('data-freq', freq.val());
      }

      function setDataFreq(element) {
        var wrapper = $(element).parents('fieldset');
        var freq = wrapper.find('.recur-repeat');
        freq.prop('data-freq', freq.val());
      }

      function toggleMinutesHours(element) {
        var wrapper = $(element).parents('fieldset');
        var freq = wrapper.find('.recur-repeat');
        var option_minutes = freq.find("option[value = 'MINUTELY']");
        var option_hours = freq.find("option[value = 'HOURLY']");
        var is_checked = $(element).prop('checked');
        if (is_checked) {
          option_minutes.attr("disabled", "disabled");
          option_hours.attr("disabled", "disabled");
        }
        else {
          option_minutes.removeAttr("disabled");
          option_hours.removeAttr("disabled");
        }
      }
    }
  };
})(jQuery, Drupal);
