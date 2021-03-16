WEBFORM MAILCHIMP 8.x - 5.x
===========================

This module allows to send Webform submissions to MailChimp list.


REQUIREMENTS
------------

- Drupal 8.x
- Webform module 8.x-5.x (https://www.drupal.org/project/webform).
- MailChimp module (https://www.drupal.org/project/mailchimp).
- MailChimp account (http://mailchimp.com) with at least one list.
- MailChimp API PHP library (MailChimp module dependency).


INSTALLATION
------------

1. Unzip the files to the "sites/all/modules" OR "modules" directory and enable the module.
Of course, you can also download and enable it with drush.

2. Go to Webforms list page (admin/structure/webform) and click "Edit" on desired Webform.

3. Click Emails/Handlers secondary tab and then click on "Add handler" button.

4. Click on "Add handler" button on "MailChimp" row.

5. Fill in the form. You should have at least one list in your MailChimp account, and at least one Email field in
your Webform.

6. If you want to map extra fields, create a sign-up form at your MailChimp account (Signup forms => General forms).
Add as many form items as you want, but take into account "Field tag" of each one. Each field you have configured
in your Webform, will be mapped there if the "key" value in the Webform matches "Field tag" value in MailChimp.
Don't worry about upper/lowercase.



SUPPORT
-------

Donation is possible by contacting me via grisendo@gmail.com


CREDITS
-------

8.x-5.x Developed and maintained by grisendo (https://www.drupal.org/user/848238)
