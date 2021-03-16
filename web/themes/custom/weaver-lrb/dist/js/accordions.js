"use strict";

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.weaver_accordions = {
    accordionIconToggle: function accordionIconToggle(accordionItem) {
      var accordionIcon = accordionItem.querySelector('.icon-morph-group');
      var original, before, after;
      original = accordionIcon.querySelector('.morph-original path');
      before = accordionIcon.querySelector('.morph-beep path');
      after = accordionIcon.querySelector('.morph-boop path');

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
    accordionToggle: function accordionToggle(accordion, action) {
      $(accordion).collapse(action);
    },
    checkAllOpen: function checkAllOpen(accordionGroup) {
      var allOpen = true; // check status of each accordion

      accordionGroup.querySelectorAll('.field--name-field-wv-accordion-group .collapse').forEach(function (accordionItem) {
        if (accordionItem.classList.contains('show') == false) {
          allOpen = false;
        }
      });
      return allOpen;
    },
    toggleAccordionAllButton: function toggleAccordionAllButton(allButton) {
      var closedText = allButton.dataset.closedText;
      var openText = allButton.dataset.openText;
      var accordionGroup = allButton.closest('.paragraph--type--wv-accordion');
      var allOpen = this.checkAllOpen(accordionGroup); // if all accordion items are open, change to CLOSE ALL

      if (allOpen == true) {
        allButton.textContent = closedText;
      } else {
        allButton.textContent = openText;
      }
    },
    attach: function attach(context, settings) {
      var weaver = Drupal.behaviors.weaver_accordions; // ensures javascript runs only once per page load and not with every ajax call

      $('body', context).once('weaver_accordions').each(function () {
        // Accordion Open All
        var accordionOpenAllButtons = document.querySelectorAll('.accordion-toggle-all');

        if (accordionOpenAllButtons) {
          // add actions whenever ALL button clicked
          accordionOpenAllButtons.forEach(function (openAllButton) {
            openAllButton.addEventListener('click', function () {
              // toggle icon and open/close
              var accordionGroup = openAllButton.closest('.paragraph--type--wv-accordion');
              var accordions = accordionGroup.querySelectorAll('.collapse');
              var allOpen = weaver.checkAllOpen(accordionGroup);
              accordions.forEach(function (accordion) {
                if (allOpen == false) {
                  weaver.accordionToggle(accordion, 'show');
                } else {
                  weaver.accordionToggle(accordion, 'hide');
                }
              });
            });
          });
        }

        var accordions = document.querySelectorAll('.paragraph--type--wv-accordion-item .collapse');

        if (accordions) {
          accordions.forEach(function (accordion) {
            // accordions respond to Enter and spacebar
            document.addEventListener('keydown', function (e) {
              if (e.code == 'Enter' && e.target.classList.contains('accordion-toggle')) {
                $(accordion).collapse('toggle');
              }

              if (e.code == 'Escape' && e.target.classList.contains('accordion-toggle')) {
                $(accordion).collapse('hide');
              } // open all accordions when CTRL + F used to search


              if (e.keyCode == 70 && e.ctrlKey) {
                $(accordion).collapse('show');
              }
            });
            $(accordion).on('shown.bs.collapse hidden.bs.collapse', function () {
              var accordionItem = accordion.parentElement;
              weaver.accordionIconToggle(accordionItem); //toggle all button

              var allButton = accordionItem.closest('.paragraph--type--wv-accordion').querySelector('.accordion-toggle-all');
              weaver.toggleAccordionAllButton(allButton);
            });
          });
        }

        var accordionParagraphs = document.querySelectorAll('.paragraph--type--wv-accordion');

        if (accordionParagraphs) {
          accordionParagraphs.forEach(function (accordionParagraph) {
            // check if next paragraph is also an accordion and remove margin-bottom from .field__item if so
            var nextSibling = accordionParagraph.parentNode.nextElementSibling;

            if (nextSibling != null && nextSibling.classList.contains('field__item')) {
              var nextParagraph = nextSibling.querySelector('.paragraph');

              if (nextParagraph != null && nextParagraph.classList.contains('paragraph--type--wv-accordion')) {
                nextParagraph.classList.add('mt-0');
                accordionParagraph.classList.add('mb-0');
                accordionParagraph.parentNode.classList.add('mb-0');
              }
            }
          });
        }
      });
    }
  };
})(jQuery, Drupal);
//# sourceMappingURL=accordions.js.map
