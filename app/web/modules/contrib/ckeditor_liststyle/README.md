# List Style Plugin

## Description
This plugin adds numbered list and ordered list properties dialogs (available
in context menu).

They allow setting:

* list type (e.g. circle, square, dot for bulleted list or decimal, lower/upper
  roman, lower/upper alpha for numbered list)
* start number (for numbered list).

## Known issue
Any text format with `Limit allowed HTML tags and correct faulty HTML` filter enabled will strip the `style` attribute from ANY tags. See [FilterHtml](https://api.drupal.org/api/drupal/core%21modules%21filter%21src%21Plugin%21Filter%21FilterHtml.php/class/FilterHtml) class:

> The 'style' and 'on*' ('onClick' etc.) attributes are always forbidden, and are removed by Xss::filter()

However, the [CKEditor 4 List Style](https://ckeditor.com/cke4/addon/liststyle) plugin sets the list properties via inline styles.

That limits this add-on's usage only to text-formats NOT using `Limit allowed HTML tags and correct faulty HTML` filter. i.e. *Full HTML*

## Usage
Right click on any numbered or ordered list in CKEditor to open the context
menu.

## Installation
This module requires:

- `drupal/ckeditor`: Drupal core module _CKEditor_.

- [CKEditor 4 List Style](https://ckeditor.com/cke4/addon/liststyle): A CKEditor4 plugin that adds numbered list and ordered list properties dialogs (available in context menu).

### Install using Composer (recommended)
If you use Composer to manage dependencies, edit `/composer.json` as follows.

- Run `composer require --prefer-dist composer/installers` to ensure that you have the `composer/installers` package installed. This package facilitates the installation of packages into directories other than `/vendor` (e.g. `/libraries`) using Composer.

- Add one of the following to the "installer-paths" section of `composer.json`, or alter as necessary:

``` json
"libraries/{$name}": ["type:drupal-library"],
```
*or*

``` json
"libraries/ckeditor/plugins/{$name}": ["type:drupal-library"],
```

- Add the following to the "repositories" section of `composer.json`:

``` json
{
    "type": "package",
    "package": {
        "name": "ckeditor/liststyle",
        "version": "4.8.0",
        "type": "drupal-library",
        "extra": {
          "installer-name": "liststyle"
        },
        "dist": {
            "url": "https://download.ckeditor.com/liststyle/releases/liststyle_4.8.0.zip",
            "type": "zip"
        }
    }
}
```

- Run `composer require --prefer-dist 'ckeditor/liststyle:4.8.0'` - you should find that new directory has been created under `/libraries`

- Then install this module: `composer require 'drupal/ckeditor_liststyle:^1.3'`

### Install manually
- Open https://ckeditor.com/cke4/addon/liststyle and download the *[version: 4.8.0](https://download.ckeditor.com/liststyle/releases/liststyle_4.8.0.zip)*.

- Extract the content and copy to *libraries* folder. i.e. `/libraries/liststyle/plugin.js`

- Download [CKEditor List Style](https://www.drupal.org/project/ckeditor_liststyle) (this module) and then extract files into `/modules/contrib/ckeditor_liststyle`

## Resources
[Other contributed modules and plug-ins available for CKEditor](https://www.drupal.org/documentation/modules/ckeditor/contrib)

## Maintainers
Osman Gormus - https://www.drupal.org/u/osman
