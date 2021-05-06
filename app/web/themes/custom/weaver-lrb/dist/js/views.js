"use strict";

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.weaver_views = {
    getOffsetTop: function getOffsetTop(element) {
      var offsetTop = 0;

      while (element) {
        offsetTop += element.offsetTop;
        element = element.offsetParent;
      }

      return offsetTop - 120;
    },
    attach: function attach(context, settings) {
      var weaver = Drupal.behaviors.weaver_views;

      var queryArrayToString = function queryArrayToString(args) {
        var queryString = '';
        $.each(args, function (name, value) {
          queryString = queryString + name + '=' + encodeURIComponent(value) + '&';
        });
        return queryString.slice(0, -1);
      };

      var scrollToSearchResults = function scrollToSearchResults(viewClass) {
        var searchResults = document.querySelector(viewClass);

        if (searchResults) {
          var searchQuery = window.location.search; // if a search query is present, scroll to results

          if (searchQuery) {
            var searchScrollTarget = searchResults.querySelector('.view-content') ? searchResults.querySelector('.view-content') : searchResults.querySelector('.view-empty');

            if (searchResults.querySelector('.view-results-summary')) {
              searchScrollTarget = searchResults.querySelector('.view-results-summary'); // stops overlap with Drupal's scroll event

              setTimeout(function () {
                window.scrollTo(0, Drupal.behaviors.weaver_views.getOffsetTop(searchScrollTarget));
              }, 500);
            }
          }
        }
      }; ///////////////////////////////////////
      // SEARCH
      // auto scroll to results if there are some
      // ensures javascript runs only once per page load and not with every ajax call
      // $('body', context).once('weaver_accordions').each(function () {
      // SEARCH RESULTS


      scrollToSearchResults('.lrb-search-view'); // });
      // runs every ajax call

      $(document).ajaxComplete(function (event, xhr, settings) {
        // get current parameters
        var query = window.location.search; // get parameters from Ajax

        var ajaxParams = parseQueryString(query); // COLLECTIVE AGREEMENTS
        // update grouping options when form submitted

        var caView = document.querySelector('.view-lrb-collective-agreements');

        if (caView) {
          var decisionsGroupingLinks = document.querySelectorAll('.view-grouping-options a');

          if (ajaxParams && decisionsGroupingLinks) {
            // add new parameters to the grouping options
            decisionsGroupingLinks.forEach(function (groupingLink) {
              // remove existing parameters
              var existingUrl = groupingLink.href;
              var newUrl = existingUrl;
              var pos = existingUrl.indexOf('?');

              if (pos != -1) {
                var existingQuery = existingUrl.substring(pos + 1);
                newUrl = existingUrl.replace(existingQuery, '');
              }

              var queryString = queryArrayToString(ajaxParams);
              var finalUrl = newUrl + queryString;
              groupingLink.setAttribute('href', finalUrl);
            });
          }
        } // DECISIONS
        // make sure year field stays set


        var decisionsView = document.querySelector('.view-lrb-decisions');

        if (decisionsView) {
          var decisionsYearField = document.querySelector('.year-replace');

          if (ajaxParams.year && ajaxParams.year != '') {
            decisionsYearField.value = ajaxParams.year;
          }
        } // ALL SEARCH-STYLE RESULTS


        if (event.target.activeElement.classList.contains('form-autocomplete') == false) {
          scrollToSearchResults('.lrb-search-view');
        }
      });

      var parseQueryString = function parseQueryString(query) {
        var args = {};
        var pos = query.indexOf('?');

        if (pos != -1) {
          query = query.substring(pos + 1);
        }

        var pairs = query.split('&');
        var pair, key, value;

        for (var i in pairs) {
          if (typeof pairs[i] == 'string') {
            pair = pairs[i].split('='); // Ignore the 'q' path argument, if present.

            if (pair[0] != 'q' && pair[1]) {
              key = decodeURIComponent(pair[0].replace(/\+/g, ' '));
              value = decodeURIComponent(pair[1].replace(/\+/g, ' ')); // Field name ends with [], it's multivalues.

              var inArrayCheck = $.inArray(value, args[key]);

              if (/\[\]$/.test(key)) {
                if (!(key in args)) {
                  args[key] = [value];
                } // Don't duplicate values.
                else if (inArrayCheck !== -1) {
                    args[key].push(value);
                  }
              } else {
                args[key] = value;
              }
            }
          }
        }

        return args;
      };
    }
  };
})(jQuery, Drupal, drupalSettings);
//# sourceMappingURL=views.js.map
