(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.weaver_custom = {
  	activateSocialLinks: function(button, socialLinks) {
      const weaverCustom = Drupal.behaviors.weaver_custom;

      // takeoverMenu icon change
      button.addEventListener('click', function(e) {
      	e.preventDefault();
        weaverCustom.toggleSocialLinks(button, socialLinks);
      });

      document.addEventListener('keydown', function(e) {
        if (e.code == 'Enter' && e.target == button) {
          weaverCustom.toggleSocialLinks(button, socialLinks);
        }
        if (e.code == 'Escape' && weaverCustom.checkOpen()) {
          weaverCustom.toggleSocialLinks(button, socialLinks);
        }
      });
  	},

    adminToolbarStopOpening: function() {
      const toolbar = document.getElementById('toolbar-item-administration-tray');
      if (toolbar) {
        window.addEventListener('resize', () => {
          if (window.innerWidth < 992) {
            toolbar.classList.remove('is-active');
          }
        });
      }
    },

    checkOpen: function() {
      if (document.body.classList.contains('social-share-open')) {
          return true;
      }
      return false;
    },

  	toggleSocialLinks: function(button, socialLinks) {
      const weaverCustom = Drupal.behaviors.weaver_custom;

      const body = document.body;
    	let shareLinks = document.getElementById('weaver-share-links');

      // animate icon
      Drupal.behaviors.weaver_animations.morphIcons('share-alt', 'close');

      // expand/collapse menu
      body.classList.toggle('social-share-open');
      body.classList.toggle('social-share-closed');

      // take care of accessibility
      this.toggleTrueFalse(shareLinks, 'aria-hidden');
      this.toggleTrueFalse(button, 'aria-expanded');

      this.trapFocus(shareLinks);
  	},

    toggleTrueFalse: function(element, attribute) {

        let attributeStatus = element.getAttribute(attribute);

        if (attributeStatus == 'true') {
          element.setAttribute(attribute, 'false');
        } else {
          element.setAttribute(attribute, 'true');
        }
    },

    trapFocus: function(element) {
      if (element) {
        const elementLinks = element.querySelectorAll('a');

        // put all menu items into or out of tabindex
        if (this.checkOpen()) {
          elementLinks.forEach((link) => {
            link.setAttribute('tabindex', 0);
          });

        } else {
          elementLinks.forEach((link) => {
            link.setAttribute('tabindex', -1);
          });
        }
      }
    },

    attach: function (context, settings) {


        // override Year filter on Decisions search
        const decisionsYearFilter = document.querySelector('#views-exposed-form-lrb-decisions-block-1 input[name=year]');
        if (decisionsYearFilter) {
          //check if select is there also
          let yearReplace = document.querySelector('select.year-replace');
          yearReplace.addEventListener('change', function() {
            decisionsYearFilter.value = yearReplace.value;
          });
        }

        // ensures javascript runs only once per page load and not with every ajax call
        $('body', context).once('weaver_custom').each(function () {
          const weaver = Drupal.behaviors.weaver_custom;

          weaver.adminToolbarStopOpening();

          document.body.classList.add('social-share-closed');

        	let socialLinks = document.getElementById('weaver-social-sharing');
        	if (socialLinks) {

        		let shareButton = socialLinks.querySelector('.weaver-social-share a');

        		if (shareButton) {
        			weaver.activateSocialLinks(shareButton, socialLinks);
        		}
        	}

        	let shareLinks = document.getElementById('weaver-share-links');
      		weaver.trapFocus(shareLinks);

        });
    }
  };

})(jQuery, Drupal);
