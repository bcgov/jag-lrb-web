"use strict";

// special JS for the takeover menu
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.weaver_animations = {
    morphIcons: function morphIcons(iconBefore, iconAfter) {
      var original = document.querySelector('#' + iconBefore + '-original path');
      var before = document.querySelector('#' + iconBefore + '-icon path');
      var after = document.querySelector('#' + iconAfter + '-icon path');

      if (original.classList.contains('lrb-morphed')) {
        var tween = KUTE.fromTo(original, {
          path: after
        }, {
          path: before
        }, {
          duration: 200
        }).start();
      } else {
        var _tween = KUTE.fromTo(original, {
          path: before
        }, {
          path: after
        }, {
          duration: 200
        }).start();
      }

      original.classList.toggle('lrb-morphed');
    },
    attach: function attach(context, settings) {
      // ensures javascript runs only once per page load and not with every ajax call
      $('body', context).once('weaver_animations').each(function () {
        // LOGO animations
        var baseTimeout = 100;
        var logoText = document.querySelector('.navbar-logo-text');

        if (logoText) {
          var lrbLetters = logoText.querySelectorAll('path.lrb');
          var lrbRest = logoText.querySelectorAll('path:not(.lrb)'); // drop in each letter

          lrbLetters.forEach(function (lrbLetter, i) {
            lrbLetter.onanimationend = function () {
              setTimeout(function () {
                lrbLetter.classList.add('animate-end');
              }, 500);
            };

            setTimeout(function () {
              lrbLetter.classList.add('animate');
            }, baseTimeout + 200 * i); // animate the rest

            lrbRest.forEach(function (lrbRestPath) {
              lrbRestPath.onanimationend = function () {
                setTimeout(function () {
                  lrbRestPath.classList.add('animate-end');
                }, 200);
              };

              lrbRestPath.classList.add('animate');
            });
          });
        }

        var logoIconTimeout = 1000;
        var logoIconSvg = document.querySelector('.navbar-logo-icon svg');

        if (logoIconSvg) {
          logoIconSvg.classList.add('animate');
          var logoIcons = logoIconSvg.querySelectorAll('path');

          if (logoIcons) {
            logoIcons.forEach(function (logoIcon) {
              logoIcon.classList.add('animate');
            });
          }
        } // animate collapse for any elements with 'morph-collapse' class -- eg., Hearings page search options


        var morphCollapses = document.querySelectorAll('.morph-collapse');

        if (morphCollapses) {
          morphCollapses.forEach(function (collapse) {
            collapse.addEventListener('click', function () {
              var iconBefore = collapse.querySelector('.morph-original').id.replace('-original', '');
              var iconAfter = collapse.querySelector('.morph-beep').id.replace('-icon', '');
              Drupal.behaviors.weaver_animations.morphIcons(iconBefore, iconAfter);
            });
          });
        } // animate icons for mobile menu


        var mobileMenuIcons = document.querySelectorAll('.navbar-toggler .icon-morph-group');

        if (mobileMenuIcons) {
          mobileMenuIcons.forEach(function (mobileMenuIcon) {
            mobileMenuIcon.addEventListener('click', function () {
              // let iconBefore = mobileMenuIcon.querySelector('.morph-original').id.replace('-original', '');
              // let iconAfter = mobileMenuIcon.querySelector('.morph-beep').id.replace('-icon', '');
              // Drupal.behaviors.weaver_animations.morphIcons(iconBefore, iconAfter);
              var original = mobileMenuIcon.querySelector('.morph-original path');
              var after = mobileMenuIcon.querySelector('.morph-beep path');
              var before = mobileMenuIcon.querySelector('.morph-boop path');

              if (original.classList.contains('lrb-morphed')) {
                var tween = KUTE.fromTo(original, {
                  path: after
                }, {
                  path: before
                }, {
                  duration: 200
                }).start();
              } else {
                var _tween2 = KUTE.fromTo(original, {
                  path: before
                }, {
                  path: after
                }, {
                  duration: 200
                }).start();
              }

              original.classList.toggle('lrb-morphed');
            });
          });
        }
      });
    }
  };
})(jQuery, Drupal);
//# sourceMappingURL=animations.js.map
