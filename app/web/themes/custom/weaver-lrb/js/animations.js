// special JS for the takeover menu

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.weaver_animations = {
	  
	  morphIcons: function(iconBefore, iconAfter) {

	    let original = document.querySelector('#' + iconBefore + '-original path');
	    let before = document.querySelector('#' + iconBefore + '-icon path');
	    let after = document.querySelector('#' + iconAfter + '-icon path');

	    if (original.classList.contains('lrb-morphed')) {
        let tween = KUTE.fromTo(original, {path: after }, { path: before }, { duration: 200 }).start();
	    } else {
        let tween = KUTE.fromTo(original, {path: before }, { path: after }, { duration: 200 }).start();
	    }
	    
	    original.classList.toggle('lrb-morphed');
  	},

	  attach: function (context, settings) {

      // ensures javascript runs only once per page load and not with every ajax call
      $('body', context).once('weaver_animations').each(function () { 

        // LOGO animations
        let baseTimeout = 100;
        const logoText = document.querySelector('.navbar-logo-text');
        if (logoText) {
          let lrbLetters = logoText.querySelectorAll('path.lrb');
          let lrbRest = logoText.querySelectorAll('path:not(.lrb)');

          // drop in each letter
          lrbLetters.forEach((lrbLetter, i) => {
            lrbLetter.onanimationend = () => {
              setTimeout(() => {
                lrbLetter.classList.add('animate-end');
              }, 500);
            };

            setTimeout(() => {
              lrbLetter.classList.add('animate');
            }, baseTimeout + (200*i));


            // animate the rest
            lrbRest.forEach((lrbRestPath) => {
              lrbRestPath.onanimationend = () => {
                setTimeout(() => {
                  lrbRestPath.classList.add('animate-end');
                }, 200);
              };

              lrbRestPath.classList.add('animate');
            });
          });
        }

        let logoIconTimeout = 1000;

        const logoIconSvg = document.querySelector('.navbar-logo-icon svg');
        if (logoIconSvg) {
          logoIconSvg.classList.add('animate');
          
          const logoIcons = logoIconSvg.querySelectorAll('path');
          if (logoIcons) {
            logoIcons.forEach((logoIcon) => {
              logoIcon.classList.add('animate');
            });
          }
        }


      	// animate collapse for any elements with 'morph-collapse' class -- eg., Hearings page search options
      	let morphCollapses = document.querySelectorAll('.morph-collapse');
      	if (morphCollapses) {

      		morphCollapses.forEach((collapse) => {
      			collapse.addEventListener('click', function() {
      				let iconBefore = collapse.querySelector('.morph-original').id.replace('-original', '');
      				let iconAfter = collapse.querySelector('.morph-beep').id.replace('-icon', '');
      				Drupal.behaviors.weaver_animations.morphIcons(iconBefore, iconAfter);
      			});
		    	});
		    }

        // animate icons for mobile menu
        let mobileMenuIcons = document.querySelectorAll('.navbar-toggler .icon-morph-group');
        if (mobileMenuIcons) {

          mobileMenuIcons.forEach((mobileMenuIcon) => {
            mobileMenuIcon.addEventListener('click', () => {
              // let iconBefore = mobileMenuIcon.querySelector('.morph-original').id.replace('-original', '');
              // let iconAfter = mobileMenuIcon.querySelector('.morph-beep').id.replace('-icon', '');
              // Drupal.behaviors.weaver_animations.morphIcons(iconBefore, iconAfter);
              let original = mobileMenuIcon.querySelector('.morph-original path');
              let after = mobileMenuIcon.querySelector('.morph-beep path');
              let before = mobileMenuIcon.querySelector('.morph-boop path');

              if (original.classList.contains('lrb-morphed')) {
                let tween = KUTE.fromTo(original, {path: after }, { path: before }, { duration: 200 }).start();
              } else {
                let tween = KUTE.fromTo(original, {path: before }, { path: after }, { duration: 200 }).start();
              }
              
              original.classList.toggle('lrb-morphed');
            });
          });
        }

      });
    }
  };


})(jQuery, Drupal);
