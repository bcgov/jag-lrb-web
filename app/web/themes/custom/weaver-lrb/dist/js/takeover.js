"use strict";

// special JS for the takeover menu
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.weaver_takeover = {
    activateTakeover: function activateTakeover(type, button, cover) {
      var weaverTakeover = Drupal.behaviors.weaver_takeover; // takeoverMenu icon change

      button.addEventListener('click', function (e) {
        e.preventDefault();
        weaverTakeover.toggleTakeover(type, button, cover);
      });
      document.addEventListener('keydown', function (e) {
        if (e.code == 'Enter' && e.target == button) {
          weaverTakeover.toggleTakeover(type, button, cover);
        }

        if (e.code == 'Escape' && weaverTakeover.checkOpen(type)) {
          weaverTakeover.toggleTakeover(type, button, cover);
        }
      }); // actions when takeoverCover clicked

      if (cover) {
        cover.onclick = function (e) {
          e.preventDefault();
          weaverTakeover.toggleTakeover(type, button, cover);
        };
      }
    },
    checkClosed: function checkClosed(type) {
      if (document.querySelector('body').classList.contains('takeover-' + type + '-open')) {
        return false;
      }

      return true;
    },
    checkOpen: function checkOpen(type) {
      if (document.querySelector('body').classList.contains('takeover-' + type + '-open')) {
        return true;
      }

      return false;
    },
    mobileTakeoverChanges: function mobileTakeoverChanges(takeover) {
      var childNav;
      var parentLinks = takeover.querySelectorAll('ul.nav > li.nav-item > .nav-link');

      if (parentLinks) {
        parentLinks.forEach(function (parentLink) {
          parentLink.addEventListener('click', function (e) {
            e.preventDefault();
            childNav = parentLink.parentNode.querySelector('ul.menu');
            childNav.classList.toggle('active');
          });
        });
      }
    },
    toggleTakeover: function toggleTakeover(type, button, cover) {
      var weaverTakeover = Drupal.behaviors.weaver_takeover;
      var body = document.querySelector('body');
      var header = document.getElementById('header');
      var sideMenu = document.getElementById('side-menu');
      var takeover = document.getElementById('takeover-' + type);
      var toggleButton;
      var icon; // ensure all other takeovers are closed

      switch (type) {
        case 'menu':
          toggleButton = document.querySelector('#takeover-button a');

          if (this.checkOpen('search')) {
            this.toggleTakeover('search', button, cover);
          }

          icon = 'bars';
          break;

        case 'search':
          toggleButton = document.querySelector('#search-button a');

          if (this.checkOpen('menu')) {
            this.toggleTakeover('menu', button, cover);
          }

          icon = 'search';
          break;
      } // animate icon


      Drupal.behaviors.weaver_animations.morphIcons(icon, 'close'); // expand/collapse menu

      body.classList.toggle('takeover-' + type + '-open');
      body.classList.toggle('takeover-' + type + '-closed'); // take care of accessibility

      this.toggleTrueFalse(takeover, 'aria-hidden');
      this.toggleTrueFalse(toggleButton, 'aria-expanded'); // close tooltips

      $('[data-toggle="tooltip"]').tooltip('hide');
      this.trapFocus(type);
    },
    toggleTrueFalse: function toggleTrueFalse(element, attribute) {
      var attributeStatus = element.getAttribute(attribute);

      if (attributeStatus == 'true') {
        element.setAttribute(attribute, 'false');
      } else {
        element.setAttribute(attribute, 'true');
      }
    },
    trapFocus: function trapFocus(type) {
      var takeoverMenuLinks = document.getElementById('takeover-menu').querySelectorAll('a.nav-link'); // put all menu items into or out of tabindex

      if (this.checkOpen(type)) {
        switch (type) {
          case 'menu':
            takeoverMenuLinks.forEach(function (link) {
              link.setAttribute('tabindex', 0);
            });
            break;
        }
      } else {
        switch (type) {
          case 'menu':
            takeoverMenuLinks.forEach(function (link) {
              link.setAttribute('tabindex', -1);
            });
            break;
        }
      }
    },
    attach: function attach(context, settings) {
      // ensures javascript runs only once per page load and not with every ajax call
      $('body', context).once('weaver_takeover').each(function () {
        var weaverTakeover = Drupal.behaviors.weaver_takeover; // add closed classes

        var body = document.querySelector('body');
        body.classList.add('takeover-menu-closed');
        body.classList.add('takeover-search-closed'); // MENU

        var takeover = document.getElementById('takeover-menu');
        var takeoverButton = document.getElementById('takeover-button').querySelector('a[data-target=takeover-menu]');
        var takeoverCover = document.getElementById('takeover-menu-cover');

        if (takeover) {
          // hide when page loads or else get FOUC
          takeover.classList.add('launch');
          setTimeout(function () {
            takeover.classList.remove('launch');
          }, 1000);

          if (takeoverButton) {
            weaverTakeover.activateTakeover('menu', takeoverButton, takeoverCover);
          }

          var tabHandle;
          takeoverButton.addEventListener('click', function (e) {
            e.preventDefault(); // IE fix, turns link into anchor to footer

            var ie = 0;

            try {
              ie = navigator.userAgent.match(/(MSIE |Trident.*rv[ :])([0-9]+)/)[2];

              if (ie !== 0) {
                takeoverButton.removeAttribute('data-target');
                takeoverButton.removeAttribute('href');
                var footerMenu = document.getElementById('footer-menu');
                var footerPosition = footerMenu.getBoundingClientRect().top + window.pageYOffset - 150;
                $([document.documentElement, document.body]).animate({
                  scrollTop: footerPosition
                }, 500);
              }
            } catch (exception) {}

            if (tabHandle) {
              tabHandle.disengage();
              tabHandle = null;
            } else {
              tabHandle = ally.maintain.tabFocus({
                context: header
              });
            }
          });
          var mobileToggler = document.querySelector('.navbar-toggler');

          if (mobileToggler) {
            weaverTakeover.activateTakeover('menu', mobileToggler, takeoverCover);
            takeover.classList.add('mobile-active');
          } // SEARCH


          var takeoverSearch = document.getElementById('takeover-search');
          var searchButtons = document.querySelectorAll('a[data-target=takeover-search]');
          var searchCover = document.getElementById('takeover-search-cover');

          if (takeoverSearch) {
            // hide when page loads or else get FOUC
            takeoverSearch.classList.add('launch');
            setTimeout(function () {
              takeoverSearch.classList.remove('launch');
            }, 1000);

            if (searchButtons) {
              searchButtons.forEach(function (searchButton) {
                weaverTakeover.activateTakeover('search', searchButton, searchCover);
              });
            }
          } // on mobile, accordion style links


          if (window.innerWidth < 992) {
            weaverTakeover.mobileTakeoverChanges(takeover);
          }

          window.addEventListener('resize', function () {
            if (window.innerWidth < 992) {
              weaverTakeover.mobileTakeoverChanges(takeover);
            }
          });
        }
      });
    }
  };
})(jQuery, Drupal);
//# sourceMappingURL=takeover.js.map
