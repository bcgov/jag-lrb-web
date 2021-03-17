Webform Navigation
---------------

### About this Module

This module creates a navigation setting for webform that allows users to navigate forwards and backwards through wizard
pages when the wizard progress bar is enabled. It performs and logs the validation when a user navigates away from a page.
Then displays any errors on a page when the user navigates back to it.

### Configuration

Once you enable the module, go to your webform's settings page `/admin/structure/webform/manage/webform_machine_name/settings`
and scroll to the "Third Party Settings" section. You should find a new section within these settings titled
"Webform Navigation Settings". To allow for forward navigation simply check the checkbox labeled "Allow forward
navigation when the wizard progress bar is enabled" checkbox. To allow for bypassing validation when a user presses the
"Next" button check the "Prevent validation when the user presses the "Next Page" button" checkbox. Insure the
navigation progress bar is enabled by visiting `/admin/structure/webform/manage/webform_machine_name/settings/form` and
checking the "Show wizard progress bar" checkbox. Finally you will need to enable the webform navigation handler in the
Emails / Handlers tab: `/admin/structure/webform/manage/webform_machine_name/settings/handlers`

**Note:** Enabling forward navigation will also enable submission logging and saving of drafts for all users.
