(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.weaver_views = {

    attach: function (context, settings) {

      const weaver = Drupal.behaviors.weaver_views;

			const getOffsetTop = element => {
			  let offsetTop = 0;
			  while(element) {
			    offsetTop += element.offsetTop;
			    element = element.offsetParent;
			  }
			  return offsetTop - 120;
			};

		  const queryArrayToString = function(args) {
		  	let queryString = '';
		    $.each(args, function (name, value) {
		    	queryString = queryString + name + '=' + encodeURIComponent(value) + '&';
		    });

	      return queryString.slice(0, -1);
		  };

		  const scrollToSearchResults = function(viewClass) {
	      let searchResults = document.querySelector(viewClass);
	      if (searchResults) {
	        let searchQuery = window.location.search;
	        
	        // if a search query is present, scroll to results
	        if (searchQuery) {

            let searchScrollTarget = searchResults.querySelector('.view-content') ? searchResults.querySelector('.view-content') : searchResults.querySelector('.view-empty');
            if (searchResults.querySelector('.view-results-summary')) {
              searchScrollTarget = searchResults.querySelector('.view-results-summary');
            }
            window.scrollTo(0, getOffsetTop(searchScrollTarget));
	        }
	      }
	    };

      ///////////////////////////////////////
      // SEARCH
      // auto scroll to results if there are some

      // ensures javascript runs only once per page load and not with every ajax call
      // $('body', context).once('weaver_accordions').each(function () {
        // SEARCH RESULTS
        scrollToSearchResults('.lrb-search-view');
      // });

      // runs every ajax call
      $(document).ajaxComplete(function (event, xhr, settings) {
      	console.log(event);

        // get current parameters
        let query = window.location.search;

      	// get parameters from Ajax
      	let ajaxParams = parseQueryString(query);

      	// COLLECTIVE AGREEMENTS
      	// update grouping options when form submitted
        var caView = document.querySelector('.view-lrb-collective-agreements');

        if (caView) {
          
          let decisionsGroupingLinks = document.querySelectorAll('.view-grouping-options a');
        	
        	if (ajaxParams && decisionsGroupingLinks) {

            // add new parameters to the grouping options
          	decisionsGroupingLinks.forEach((groupingLink) => {
          		// remove existing parameters
          		let existingUrl = groupingLink.href;
          		let newUrl = existingUrl;
          		let pos = existingUrl.indexOf('?');

					    if (pos != -1) {
					      let existingQuery = existingUrl.substring(pos + 1);
						   	newUrl = existingUrl.replace(existingQuery, '');
					    }

          		let queryString = queryArrayToString(ajaxParams);
							let finalUrl = newUrl + queryString;

							groupingLink.setAttribute('href', finalUrl);
          	});
          }            
        }

        // DECISIONS
        // make sure year field stays set
        var decisionsView = document.querySelector('.view-lrb-decisions');

        if (decisionsView) {
        	let decisionsYearField = document.querySelector('.year-replace');
        	if (ajaxParams.length && ajaxParams.year.length) {
        		decisionsYearField.value = ajaxParams.year;
        	}
        }

        // ALL SEARCH-STYLE RESULTS
        scrollToSearchResults('.lrb-search-view');

      });

		  var parseQueryString = function (query) {
		    var args = {};
		    var pos = query.indexOf('?');
		    if (pos != -1) {
		      query = query.substring(pos + 1);
		    }
		    var pairs = query.split('&');
		    var pair, key, value;
		    for (var i in pairs) {
		      if (typeof(pairs[i]) == 'string') {
		        pair = pairs[i].split('=');
		        // Ignore the 'q' path argument, if present.
		        if (pair[0] != 'q' && pair[1]) {
		          key = decodeURIComponent(pair[0].replace(/\+/g, ' '));
		          value = decodeURIComponent(pair[1].replace(/\+/g, ' '));
		          // Field name ends with [], it's multivalues.
		          let inArrayCheck = $.inArray(value, args[key]);
		          if (/\[\]$/.test(key)) {
		            if (!(key in args)) {
		              args[key] = [value];
		            }
		            // Don't duplicate values.
		            else if (inArrayCheck !== -1) {
		              args[key].push(value);
		            }
		          }
		          else {
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
