"use strict";

/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.weaver = {
    backTop: function backTop() {
      window.scrollTo(0, 0);
      document.querySelector('#takeover-button a').focus();
    },
    hoverFriends: function hoverFriends(element, friend) {
      function friendAction(element, friend) {
        friend.classList.toggle('hover-friend');
        element.classList.toggle('hover-friend');
      }

      element.addEventListener('mouseover', function () {
        friendAction(element, friend);
      });
      friend.addEventListener('mouseover', function () {
        friendAction(element, friend);
      });
      element.addEventListener('mouseout', function () {
        friendAction(element, friend);
      });
      friend.addEventListener('mouseout', function () {
        friendAction(element, friend);
      });
    },
    isInSubpages: function isInSubpages(node) {
      while (node != null && node != undefined) {
        if (node.classList != undefined) {
          if (node.classList.contains('subpage-container')) {
            return true;
          }
        }

        node = node.parentNode;
      }

      return false;
    },
    navbarResize: function navbarResize(scrollPosition) {
      var navbar = document.getElementById('header').querySelector('.navbar');

      if (navbar) {
        if (scrollPosition > 130 && window.innerWidth > 767) {
          document.body.classList.add('navbar-reduced');
        } else {
          document.body.classList.remove('navbar-reduced');
        }
      }
    },
    attach: function attach(context, settings) {
      // ensures javascript runs only once per page load and not with every ajax call
      $('body', context).once('weaver').each(function () {
        var weaver = Drupal.behaviors.weaver; /////////////////////////////////////////
        // BACK TO TOP

        document.getElementById('back-top').addEventListener('click', function () {
          weaver.backTop();
        }); /////////////////////////////////////////
        // BLOCKQUOTE

        var blockquotes = document.querySelectorAll('blockquote');

        if (blockquotes) {
          blockquotes.forEach(function (blockquote) {
            // ensures that blockquotes with long first paragraphs that contain links will look good
            if (blockquote.querySelector('.fontawesome-icon-inline')) {
              var icon = blockquote.querySelector('.fontawesome-icon-inline');
              var firstParagraph = blockquote.querySelector('p'); // remove the icon

              blockquote.querySelector('.fontawesome-icon-inline').remove(); // wrap remaining text in a span

              var newOutput = icon.outerHTML + '<span>' + firstParagraph.innerHTML + '</span>';
              firstParagraph.innerHTML = newOutput;
            }
          });
        } /////////////////////////////////////////
        // CALLOUTS


        var callouts = document.querySelectorAll('.callout');
        var calloutIcon = '<i class="fal fa-comment-exclamation"></i>';

        if (callouts) {
          callouts.forEach(function (callout) {
            // substitute a custom icon if user has altered
            if (callout.dataset.icon) {
              calloutIcon = '<i class="fal fa-' + callout.dataset.icon + '"></i>';
            } // add span wrapper around text


            callout.innerHTML = calloutIcon + '<span>' + callout.innerHTML + '</span>';
          });
        } /////////////////////////////////////////
        // LOGOS
        // if logo icon is hovered, hover for logo text and vice versa


        var logoIcon = document.querySelector('.navbar-logo-icon');
        var logoText = document.querySelector('.navbar-logo-text');
        weaver.hoverFriends(logoIcon, logoText); /////////////////////////////////////////
        // MAILCHIMP
        // pop up modal on textfield click

        var mailchimpTextfield = document.getElementById('lrb-mailchimp-signup').querySelector('input[name=lrb-mailchimp]');

        if (mailchimpTextfield) {
          mailchimpTextfield.addEventListener('click', function () {
            $('#mailchimp-signup-modal').modal('show');
          });
        } /////////////////////////////////////////
        // NAVBAR
        // resize navbar on scroll


        var ticking = false;
        window.addEventListener('scroll', function () {
          var scrollPosition = window.scrollY;

          if (!ticking) {
            window.requestAnimationFrame(function () {
              weaver.navbarResize(scrollPosition);
              ticking = false;
            });
            ticking = true;
          }
        }); ///////////////////////////////////////
        // POPOVERS
        // enable all popovers

        $('[data-toggle="popover"]').popover({
          html: true
        }); ///////////////////////////////////////
        // FIRST CONTAINERS
        // if Resources or image is in right column and is first on the page, move the page title down into the left column

        var pageTitle = document.getElementById('block-weaver-page-title'); // only on topics page

        if (document.body.classList.contains('node--type-lrb-topic') || document.body.classList.contains('node--type-wv-content-page')) {
          // only if first paragraph on page is a two-column container and text block is first paragraph in left column
          var firstParagraph = document.querySelector('.field--name-field-wv-content .field__item').children[0];

          if (firstParagraph.classList.contains('paragraph--type--wv-container')) {
            var leftColumnParagraph = firstParagraph.querySelector('.field--name-field-wv-container-content .field__item').children[0];

            if (leftColumnParagraph.classList.contains('paragraph--type--weaver-text')) {
              // move Page Title into this text block
              leftColumnParagraph.querySelector('.field__item').prepend(pageTitle); // add class to body

              document.body.classList.add('title-moved');
            }
          } // if first element on a page is two-column text and image and image is on the right, move the page title down into the left column


          if (firstParagraph.classList.contains('paragraph--type--weaver-twocol-text-image') && firstParagraph.classList.contains('image-right')) {
            var leftColumn = firstParagraph.querySelector('.text-col .text-formatted');
            leftColumn.prepend(pageTitle);
          }
        } ///////////////////////////////////////
        // SOCIAL LINKS (News Pages)
        // expand social links when share button clicked


        var newsShare = document.getElementById('news-share');

        if (newsShare) {
          newsShare.addEventListener('click', function () {
            newsShare.classList.toggle('active');
          });
        } ///////////////////////////////////////
        // TABLES
        // // turn tables added via CKEditor into responsive ones


        var responsiveTablesText = function responsiveTablesText(responsiveTables) {
          responsiveTables.forEach(function (rTable) {
            // count number of table rows
            var rTableRows = rTable.querySelectorAll('tr');

            if (rTable.parentNode.parentNode.classList.contains('view-lrb-collective-agreements') == false) {
              $(rTable).prepend('<p class="text-center font-size-sm d-block d-lg-none">Scroll left and right to view more columns</p>');

              if (rTableRows.length > 10) {
                $(rTable).append('<p class="text-center font-size-sm d-block d-lg-none">Scroll left and right to view more columns</p>');
              }
            } else {
              var rTableContainer = rTable.parentNode;
              $(rTableContainer).once().prepend('<p class="text-center font-size-sm d-block d-lg-none">Scroll left and right to view more columns</p>');
              $(rTableContainer).once().append('<p class="text-center font-size-sm d-block d-lg-none">Scroll left and right to view more columns</p>');
            }
          });
        };

        var responsiveTables = document.querySelectorAll('.table-responsive');

        if (responsiveTables) {
          responsiveTablesText(responsiveTables);
        }

        $(document).ajaxComplete(function (event, xhr, settings) {
          var responsiveTables = document.querySelectorAll('.table-responsive');

          if (responsiveTables) {
            responsiveTablesText(responsiveTables);
          }
        }); ///////////////////////////////////////
        // TERRITORIAL ACKNOWLEDGEMENT
        // highlight when scrolled to

        var territorialLinks = document.querySelectorAll('a[href="#territorial"]');
        var territorialArea = document.getElementById('territorial');

        if (territorialLinks && territorialArea) {
          territorialLinks.forEach(function (territorialLink) {
            territorialLink.addEventListener('click', function () {
              territorialArea.classList.add('animate');
              territorialArea.addEventListener('transitionend', function () {
                territorialArea.classList.remove('animate');
              });
            });
          });
        } ///////////////////////////////////////
        // TOOLTIPS


        var tooltipStatus = true; // don't open tooltips if takeover active

        if (Drupal.behaviors.weaver_takeover.checkOpen('menu') || Drupal.behaviors.weaver_takeover.checkOpen('search')) {
          tooltipStatus = false;
        }

        if (window.innerWidth < 992) {
          tooltipStatus = false;
        } // enable all tooltips, but not on mobile


        if (tooltipStatus === true) {
          var tooltipToggles = document.querySelectorAll('[data-toggle="tooltip"]');

          if (tooltipToggles) {
            tooltipToggles.forEach(function (tooltipToggle) {
              if (tooltipToggle.dataset.target != null && tooltipToggle.dataset.target == 'takeover-menu' || tooltipToggle.classList.contains('btn-round')) {
                $(tooltipToggle).tooltip({
                  animation: true,
                  delay: {
                    show: 500
                  },
                  trigger: 'hover',
                  template: '<div class="tooltip tooltip-yellow" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'
                });
              } else {
                $(tooltipToggle).tooltip({
                  animation: true,
                  delay: {
                    show: 500
                  },
                  trigger: 'hover'
                });
              }
            });
          }

          var newsletterButton = document.getElementById('newsletter-button');

          if (newsletterButton) {
            $(newsletterButton).tooltip({
              animation: true,
              delay: {
                show: 500
              },
              trigger: 'hover'
            });
          }
        } //////////////////////////////////////
        // TOPICS GRID


        var topicCards = document.querySelectorAll('.topics-grid-card');

        if (topicCards) {
          topicCards.forEach(function (topicCard) {
            weaver.hoverFriends(topicCard.querySelector('.image-container a'), topicCard.querySelector('.topics-grid-text h3 a')); // close other topics dropdowns if one is opened

            var subpages = topicCard.querySelector('.topic-subpages');
            var topicCardButton = topicCard.querySelector('.topics-subpages-button');
            $(subpages).on('show.bs.collapse', function () {
              $('.topic-subpages.show').collapse('hide');
              document.querySelectorAll('.topics-subpages-button.active').forEach(function (activeSubpagesButton) {
                activeSubpagesButton.classList.remove('active');
              });
              topicCardButton.classList.add('active');
            });
          });
          document.addEventListener('click', function (e) {
            var node = e.target.parentNode;
            var isInSubpages = weaver.isInSubpages(node);

            if (isInSubpages == false) {
              $('.topic-subpages.show').collapse('hide');
              document.querySelectorAll('.topics-subpages-button.active').forEach(function (activeSubpagesButton) {
                activeSubpagesButton.classList.remove('active');
              });
            }
          });
        } /////////////////////////////////////
        // TROUBHESHOOTER


        var troubleshooter = document.querySelector('.view-lrb-troubleshooter.view-display-id-block_1');

        if (troubleshooter) {
          $('#troubleshooter-full').on('shown.bs.collapse', function () {
            troubleshooter.classList.add('expanded-full');
          });
          $('#troubleshooter-full').on('hidden.bs.collapse', function () {
            troubleshooter.classList.remove('expanded-full');
          });
        }
      }); // KEYBOARD SHORTCUTS

      document.onkeyup = function (e) {
        // CTRL+ALT+M
        if (e.ctrlKey && e.altKey && e.which == 77) {
          // toggle Takeover Menu
          Drupal.behaviors.weaver_takeover.toggleTakeover('menu');
        } // CTRL+ALT+S


        if (e.ctrlKey && e.altKey && e.which == 83) {
          // toggle Takeover Search
          Drupal.behaviors.weaver_takeover.toggleTakeover('search');
        } // up arrow


        if (e.ctrlKey && e.altKey && e.which == 38) {
          // scroll to top
          weaver.backTop();
        } // down arrow


        if (e.ctrlKey && e.altKey && e.which == 40) {
          // scroll to bottom
          window.scrollTo(9999999, 9999999);
          document.querySelector('.region-footer-bottom a:last-child').focus();
        }
      };
    }
  };
})(jQuery, Drupal);
//# sourceMappingURL=global.js.map
