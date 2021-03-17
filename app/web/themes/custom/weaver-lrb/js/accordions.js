(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.weaver_accordions = {

    accordionIconToggle: function(accordionItem) {
      let accordionIcon = accordionItem.querySelector('.icon-morph-group');
      let original, before, after;
      original = accordionIcon.querySelector('.morph-original path');
      before = accordionIcon.querySelector('.morph-beep path');
      after = accordionIcon.querySelector('.morph-boop path');

      if (original.classList.contains('lrb-morphed')) {
          let tween = KUTE.fromTo(original, {path: after }, { path: before }, { duration: 200 }).start();
      } else {
          let tween = KUTE.fromTo(original, {path: before }, { path: after }, { duration: 200 }).start();
      }
      
      original.classList.toggle('lrb-morphed');
    },

    accordionToggle: function(accordion, action) {
      $(accordion).collapse(action);
    },

    checkAllOpen: function(accordionGroup) {
      let allOpen = true;

      // check status of each accordion
      accordionGroup.querySelectorAll('.field--name-field-wv-accordion-group .collapse').forEach((accordionItem) => {
        if (accordionItem.classList.contains('show') == false) {
          allOpen = false;
        }
      });

      return allOpen;
    },

    toggleAccordionAllButton: function(allButton) {
      let closedText = allButton.dataset.closedText;
      let openText = allButton.dataset.openText;
      let accordionGroup = allButton.closest('.paragraph--type--wv-accordion');

      let allOpen = this.checkAllOpen(accordionGroup);

      // if all accordion items are open, change to CLOSE ALL
      if (allOpen == true) {
        allButton.textContent = closedText;
      } else {
        allButton.textContent = openText;
      }
    },

    attach: function (context, settings) {

      const weaver = Drupal.behaviors.weaver_accordions;

      // ensures javascript runs only once per page load and not with every ajax call
      $('body', context).once('weaver_accordions').each(function () { 
  			
        // Accordion Open All
        const accordionOpenAllButtons = document.querySelectorAll('.accordion-toggle-all');
        if (accordionOpenAllButtons) {

          // add actions whenever ALL button clicked
          accordionOpenAllButtons.forEach((openAllButton) => {
            openAllButton.addEventListener('click', function() {

              // toggle icon and open/close
              let accordionGroup = openAllButton.closest('.paragraph--type--wv-accordion');
              let accordions = accordionGroup.querySelectorAll('.collapse');
              let allOpen = weaver.checkAllOpen(accordionGroup);

              accordions.forEach((accordion) => {
                if (allOpen == false) {
                  weaver.accordionToggle(accordion, 'show');
                } else {
                  weaver.accordionToggle(accordion, 'hide');
                }
              });
            });
          });
        }

        const accordions = document.querySelectorAll('.paragraph--type--wv-accordion-item .collapse');
        if (accordions) {

          accordions.forEach((accordion) => {
            // accordions respond to Enter and spacebar
            document.addEventListener('keydown', (e) => {
              if (e.code == 'Enter' && e.target.classList.contains('accordion-toggle')) {
                $(accordion).collapse('toggle');
              }
              if (e.code == 'Escape' && e.target.classList.contains('accordion-toggle')) {
                $(accordion).collapse('hide');
              }

              // open all accordions when CTRL + F used to search
              if (e.keyCode == 70 && e.ctrlKey) {
                $(accordion).collapse('show');
              }

            });

            $(accordion).on('shown.bs.collapse hidden.bs.collapse', function() {
              let accordionItem = accordion.parentElement;
              weaver.accordionIconToggle(accordionItem);

              //toggle all button
              let allButton = accordionItem.closest('.paragraph--type--wv-accordion').querySelector('.accordion-toggle-all');
              weaver.toggleAccordionAllButton(allButton);
            });
          });
        }

        const accordionParagraphs = document.querySelectorAll('.paragraph--type--wv-accordion');
        if (accordionParagraphs) {
          accordionParagraphs.forEach((accordionParagraph) => {
            // check if next paragraph is also an accordion and remove margin-bottom from .field__item if so
            let nextSibling = accordionParagraph.parentNode.nextElementSibling;
            if (nextSibling != null && nextSibling.classList.contains('field__item')) {
              let nextParagraph = nextSibling.querySelector('.paragraph');
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
