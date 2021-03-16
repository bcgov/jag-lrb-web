# Overview

Classy paragraphs ships a new field type "Class list" which allows an editor to
apply a selected class to paragraphs via a drop-down list.

# Installation

Install as you would normally install a contributed Drupal module. See
https://www.drupal.org/docs/8/extending-drupal-8/installing-modules for further
information.

# Configuration

Go to admin/structure/classy_paragraphs_style and create a style (set of
classes).

# Usage

- Add new "Reference" field on Paragraph Type using "Reference > Other..."
- Select type of item to reference: "Configuration > Classy Paragraphs Style"

# How It Works

We've refactored the module so it uses Drupal 8's new config entity. By doing
this we can export CSS classes (called styles), we get a UI to manage the
classes and NO MORE hooks. Also, you can add multiple classes to a single style.

By default, the class list will added to the {{ attributes.class }} twig
variable array. Insure this is added to your twig template markup. Note: if {{
attributes }} is already in your template, there is no need to add the
additional .class object.

NOTE: The module won't work if you're using Display Suite or Panelizer to
configure the paragraphs. Integration will come later.

# Requirements

- Paragraphs (https://www.drupal.org/project/paragraphs)
