(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.smartDate = {
    attach: function(context, settings) {
      $('.smartdate--widget select.field-duration').each(function(){
        setInitialDuration(this);
        augmentInputs(this);
      });

      $('.allday').once('add-allday').each(function(){
        setAllDay(this);
      }).on('change', function(){
        checkAllDay(this);
      });

      // set the step value on time inputs to hide seconds
      $('.smartdate--widget input[type="time"]').prop('step', '60');

      // update the end values when the start is changed
      $('.smartdate--widget .time-start input').on('change', function(){
        setEndDate(this);
      });

      // special handler for duration updates
      $('.smartdate--widget select.field-duration').on('change', function(){
        durationChanged(this);
      });

      // update the time
      $('.smartdate--widget .time-end').on('change', function(){
        setDuration(this);
      });

      // When the repeat frequency changes, update the advanced label.
      // $('.smartdate--widget .recur-repeat').on('change', function(){
      //   updateRepeatLabel(this);
      // }).each(function(){
      //   updateRepeatLabel(this);
      // });

      function setEndDate(element) {
        var wrapper = $(element).closest('fieldset');
        var duration_select = wrapper.find('select.field-duration');
        if (duration_select.val() === 'custom') {
          var duration = parseInt(duration_select.prop('data-duration'));
        }
        else {
          var duration = parseInt(duration_select.val());
        }
        if ((!duration && duration !== 0) || duration == 'custom') { return; }

        var start_date = wrapper.find('.time-start input[type="date"]').val();
        if (!start_date) {
          return;
        }
        var start_time = wrapper.find('.time-start input[type="time"]').val();
        if (!start_time && start_date) {
          // if only the start date has been specified, update only the end date
          wrapper.find('.time-end input[type="date"]').val(start_date);
          return
        }

        var start_array = start_time.split(':');
        if (start_date.length) {
          // Use Date objects to automatically roll over days when necessary.
          // ISO 8601 string get encoded as UTC so add the timezone offset.
          var end = new Date(Date.parse(start_date));
          var is_iso_8061 = start_date.match(/\d{4}-\d{2}-\d{2}/);
          if (is_iso_8061 && end.getTimezoneOffset() != 0) {
            end.setMinutes(end.getMinutes() + end.getTimezoneOffset());
          }
        } else {
          var end = new Date();
        }

        // Calculate and set End Time only if All Day is not checked.
        if (!(wrapper.find('input.allday').is(':checked'))) {
          end.setHours(start_array[0]);
          end.setMinutes(parseInt(start_array[1]) + duration);

          // Update End Time input.
          var end_val = pad(end.getHours(), 2) + ":" + pad(end.getMinutes(), 2);
          wrapper.find('.time-end input[type="time"]').val(end_val);
        }

        // Update End Date input.
        var new_end = end.getFullYear() + '-' + pad(end.getMonth() + 1, 2) + '-' + pad(end.getDate(), 2);
        wrapper.find('.time-end input[type="date"]').val(new_end);
      }

      function durationChanged(element) {
        var current_val = $(element).val();
        var wrapper = $(element).parents('fieldset');
        var end_time_input = wrapper.find('.time-end input[type="time"]');
        var end_date_input = wrapper.find('.time-end input[type="date"]');
        var end_date_label = wrapper.find('.time-start + .label')
        // A strict comparison is needed, but not sure which type we'll get.
        if (current_val === 0 || current_val === '0') {
          // hide the end date and time
          end_time_input.fadeOut(0);
          end_date_input.fadeOut(0);
          end_date_label.fadeOut(0);
        }
        else {
          // if they're hidden, show them
          end_time_input.fadeIn(0);
          end_date_input.fadeIn(0);
          end_date_label.fadeIn(0);
        }
        if ($(element).val() === 'custom') {
          // reset end time and add focus
          var wrapper = $(element).parents('fieldset');
          var end_time = wrapper.find('.time-end input[type="time"]');
          end_time.val('').focus();
        }
        else {
          // fire normal setEndDate()
          setEndDate(element);
        }
      }

      function setInitialDuration(element) {
        var duration = $(element).val();
        if (duration == 'custom') {
          var wrapper = $(element).parents('fieldset');
          var duration = calcDuration(wrapper);
        }
        else if (duration == 0) {
          // call this to hide the end date and time
          durationChanged(element);
        }
        // Store the numeric value in a property so it can be used programmatically
        $(element).prop('data-duration', duration);
      }

      // Add/change inputs based on initial config
      function augmentInputs(element) {
        var children = $(element).children();
        // add "All day checkbox" if config permits
        if ($(element).children('[value="custom"]').length > 0 || $(element).children('[value="1439"]').length > 0) {
          //Create the label element
          var ad_label = $("<label>").text(Drupal.t('All day') + ' ').addClass('allday-label');
          //Create the input element
          var ad_input = $('<input type="checkbox">').addClass('allday');
          //Insert the input into the label
          ad_input.prependTo(ad_label);
          $(element).parent().once('add-allday').before(ad_label);
        }
        // if a forced duration, make end date and time read only
        if ($(element).children('[value="custom"]').length == 0) {
          var wrapper = $(element).parents('fieldset');
          var end_time_input = wrapper.find('.time-end input[type="time"]');
          var end_date_input = wrapper.find('.time-end input[type="date"]');
          end_time_input.prop('readonly', true);
          end_time_input.attr('aria-readonly', true);
          end_date_input.prop('readonly', true);
          end_date_input.attr('aria-readonly', true);
        }
      }

      function setDuration(element) {
        var wrapper = $(element).parents('fieldset');
        var duration = calcDuration(wrapper);
        if (duration == 0) {
          return;
        }
        var duration_select = wrapper.find('select.field-duration');
        // Store the numeric value in a property so it can be used programmatically
        duration_select.prop('data-duration', duration);
        // Update the select to show the appropriate value
        if (duration_select.children('option[value="' + duration + '"]').length != 0){
          duration_select.val(duration);
        } else {
          duration_select.val('custom');
        }
      }

      function calcDuration(wrapper) {
        var start_time = wrapper.find('.time-start input[type="time"]').val();
        var start_date = wrapper.find('.time-start input[type="date"]').val();
        var end_time = wrapper.find('.time-end input[type="time"]').val();
        var end_date = wrapper.find('.time-end input[type="date"]').val();
        if (!start_time || !start_date || !end_time || !end_date) {
          return 0;
        }
        // split times into hours and minutes
        var start_array = start_time.split(':');
        var end_array = end_time.split(':');
        if (start_date !== end_date) {
          // The range spans more than one day, so use Date objects to calculate duration
          var start = new Date(start_date);
          start.setHours(start_array[0]);
          start.setMinutes(parseInt(start_array[1]));
          var end = new Date(end_date);
          end.setHours(end_array[0]);
          end.setMinutes(parseInt(end_array[1]));
          var duration = (end.getTime() - start.getTime()) / (60 * 1000);
        } else {
          // Convert to minutes and get the difference
          var duration = (parseInt(end_array[0]) - parseInt(start_array[0])) * 60 + (parseInt(end_array[1]) - parseInt(start_array[1]));
        }
        return duration;
      }

      function setAllDay(element) {
        var checkbox = $(element);
        var wrapper = checkbox.parents('fieldset');
        var start_time = wrapper.find('.time-start input[type="time"]');
        var start_time_label = start_time.prev('label');
        var end_time = wrapper.find('.time-end input[type="time"]');
        var end_time_label = end_time.prev('label');
        var duration = wrapper.find('select.field-duration');
        // set initial state of checkbox based on initial values
        if (start_time.val() == '00:00:00' && end_time.val() == '23:59:00') {
          checkbox.prop('checked', true);
          checkbox.prop('data-duration', duration.data('default'));
          start_time.fadeOut();
          start_time_label.fadeOut();
          end_time.fadeOut();
          end_time_label.fadeOut();
          var duration_wrapper = duration.parent();
          duration_wrapper.fadeOut(0);
        }
        else {
          checkbox.prop('data-duration', duration.val());
        }
      }

      function checkAllDay(element) {
        var checkbox = $(element);
        var wrapper = checkbox.parents('fieldset');
        var start_time = wrapper.find('.time-start input[type="time"]');
        var start_time_label = start_time.siblings('label');
        var end_time = wrapper.find('.time-end input[type="time"]');
        var end_time_label = end_time.siblings('label');
        var duration = wrapper.find('select.field-duration');
        var duration_wrapper = wrapper.find('select.field-duration').parent();

        if (checkbox.is(':checked')) {
          if (checkbox.prop('data-duration') == 0) {
            var end_date = wrapper.find('.time-end input[type="date"]');
            end_date.fadeIn(0);
            var end_date_label = wrapper.find('.time-start + .label')
            end_date_label.fadeIn(0);
          }
          // save the current start and end_date
          checkbox.prop('data-start', start_time.val());
          checkbox.prop('data-end', end_time.val());
          checkbox.prop('data-duration', duration.val());
          // set the duration to a corresponding value
          if (duration.children('option[value="custom"]').length != 0) {
            duration.val('custom');
          }
          else if (duration.children('option[value="1439"]').length != 0) {
            duration.val('1439');
          }
          // set to all day $values and hide time elements
          start_time.fadeOut(0).val('00:00');
          start_time_label.fadeOut(0);
          end_time.fadeOut(0).val('23:59');
          end_time_label.fadeOut(0);
          duration_wrapper.fadeOut(0);
        }
        else {
          // restore from data $values
          if (checkbox.prop('data-start')) {
            start_time.val(checkbox.prop('data-start'));
          }
          else {
            start_time.val('');
          }
          if (checkbox.prop('data-end')) {
            end_time.val(checkbox.prop('data-end'));
          }
          else {
            end_time.val('');
          }
          if (checkbox.prop('data-duration') || checkbox.prop('data-duration') === 0 || checkbox.prop('data-duration') === '0') {
            duration.val(checkbox.prop('data-duration'));
            duration.prop('data-duration', checkbox.prop('data-duration'));
            if (!end_time.val()) {
              setEndDate(start_time);
            }
          }
          // make time inputs visible
          start_time.fadeIn(0);
          start_time_label.fadeIn(0);
          end_time.fadeIn(0);
          end_time_label.fadeIn(0);
          duration_wrapper.fadeIn(0);
          if (duration.val() == 0) {
            // call this to hide the end date and time
            durationChanged(duration);
          }
        }
      }

      function updateRepeatLabel(element) {
        var wrapper = $(element).parents('fieldset');
        var repeat_label = wrapper.find('.field-interval + .field-suffix');
        var new_label = '';
        switch ($(element).val()) {
          case '':
            new_label = Drupal.t('times', {}, {context: "Smart Date Recur"});
            break;
          case 'DAILY':
            new_label = Drupal.t('days', {}, {context: "Smart Date Recur"});
            break;
          case 'WEEKLY':
            new_label = Drupal.t('weeks', {}, {context: "Smart Date Recur"});
            break;
          case 'MONTHLY':
            new_label = Drupal.t('months', {}, {context: "Smart Date Recur"});
            break;
          case 'YEARLY':
            new_label = Drupal.t('years', {}, {context: "Smart Date Recur"});
            break;
        }
        repeat_label.text( new_label );
      }

      function pad(str, max) {
        str = str.toString();
        return str.length < max ? pad("0" + str, max) : str;
      }
    }
  };
})(jQuery, Drupal);
