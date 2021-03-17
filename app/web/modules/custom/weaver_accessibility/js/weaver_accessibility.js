// special JS for resizing text and accessibility features

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.weaver_accessibility = {
    attach: function (context, settings) {

        // ensures javascript runs only once per page load and not with every ajax call
        $('body', context).once('weaver_accessibility').each(function () { 

        	let body = document.querySelector('body');

            // FONT RESIZER

            // set default if cookie set
            body.dataset.weaverTextResize = 3;
            if (document.cookie) {
                const fontSizeCookie = document.cookie.split('; ')
                    .find(row => row.startsWith('weaverFontSize'));
                if (fontSizeCookie) {
                    const fontSizeCookieValue = fontSizeCookie.split('=')[1];

                    if (fontSizeCookieValue.length) {
                        body.dataset.weaverTextResize = fontSizeCookieValue;
                    }
                }
            }

            let resizer = document.getElementById('weaver-text-resizer');

            if (resizer) {

                let resizerButtons = resizer.querySelectorAll('a.resizer');
                resizerButtons.forEach((button) => {
                    button.addEventListener('click', function() {
                        body.classList.add('weaver-text-resizing');

                        let resizeAction = button.dataset.resize;
                        
                        switch (resizeAction) {                        
                             case 'smaller':
                                if (body.dataset.weaverTextResize > 1) {
                                    body.dataset.weaverTextResize = parseInt(body.dataset.weaverTextResize)-1;
                                }
                                break;

                            case 'reset':
                                body.dataset.weaverTextResize = 3;
                                break;
                            
                            case 'larger':
                                if (body.dataset.weaverTextResize < 5) {
                                    body.dataset.weaverTextResize = parseInt(body.dataset.weaverTextResize)+1;
                                }
                                break;
                        }

                        // set browser cookie
                        document.cookie = "weaverFontSize=" + body.dataset.weaverTextResize;
                    });
                });
            }

        });
    }
  };

})(jQuery, Drupal);
