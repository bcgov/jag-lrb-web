/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.weaver_yearpicker = {
    attach: function (context, settings) {

        // ensures javascript runs only once per page load and not with every ajax call
        $('body', context).once('weaver_yearpicker').each(function () { 
			// $('input.datepicker-years-filter').each(function () {
		 //        $(this).datepicker('destroy');
		 //        $(this).datepicker({
		 //          disableTouchKeyboard: true,
		 //          dateFormat: 'yy',
		 //          minViewMode: 'years',
		 //          direction: "down",
		 //        });
		 //    });
			});

    }
  };

})(jQuery, Drupal);
