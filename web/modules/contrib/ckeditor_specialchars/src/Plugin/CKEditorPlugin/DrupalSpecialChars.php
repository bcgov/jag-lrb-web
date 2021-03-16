<?php

namespace Drupal\ckeditor_specialchars\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "specialChars" plugin.
 *
 * @CKEditorPlugin(
 *   id = "specialchars",
 *   label = @Translation("Special characters"),
 *   module = "ckeditor"
 * )
 */
class DrupalSpecialChars extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, CKEditorPluginContextualInterface {

  /**
   * Default characters to use.
   *
   * Copied from CKEDITOR.config.specialChars in
   * file core/assets/vendor/ckeditor/plugins/specialchar/plugin.js.
   *
   * @var array
   */
  public $defaultCharacters = [
    '!', '&quot;', '#', '$', '%', '&amp;', "'", '(', ')', '*', '+', '-', '.', '/',
    '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ':', ';',
    '&lt;', '=', '&gt;', '?', '@',
    'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
    'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
    '[', ']', '^', '_', '`',
    'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p',
    'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    '{', '|', '}', '~',
    '&euro;', '&lsquo;', '&rsquo;', '&ldquo;', '&rdquo;', '&ndash;', '&mdash;', '&iexcl;', '&cent;', '&pound;',
    '&curren;', '&yen;', '&brvbar;', '&sect;', '&uml;', '&copy;', '&ordf;', '&laquo;', '&not;', '&reg;', '&macr;',
    '&deg;', '&sup2;', '&sup3;', '&acute;', '&micro;', '&para;', '&middot;', '&cedil;', '&sup1;', '&ordm;', '&raquo;',
    '&frac14;', '&frac12;', '&frac34;', '&iquest;', '&Agrave;', '&Aacute;', '&Acirc;', '&Atilde;', '&Auml;', '&Aring;',
    '&AElig;', '&Ccedil;', '&Egrave;', '&Eacute;', '&Ecirc;', '&Euml;', '&Igrave;', '&Iacute;', '&Icirc;', '&Iuml;',
    '&ETH;', '&Ntilde;', '&Ograve;', '&Oacute;', '&Ocirc;', '&Otilde;', '&Ouml;', '&times;', '&Oslash;', '&Ugrave;',
    '&Uacute;', '&Ucirc;', '&Uuml;', '&Yacute;', '&THORN;', '&szlig;', '&agrave;', '&aacute;', '&acirc;', '&atilde;',
    '&auml;', '&aring;', '&aelig;', '&ccedil;', '&egrave;', '&eacute;', '&ecirc;', '&euml;', '&igrave;', '&iacute;',
    '&icirc;', '&iuml;', '&eth;', '&ntilde;', '&ograve;', '&oacute;', '&ocirc;', '&otilde;', '&ouml;', '&divide;',
    '&oslash;', '&ugrave;', '&uacute;', '&ucirc;', '&uuml;', '&yacute;', '&thorn;', '&yuml;', '&OElig;', '&oelig;',
    '&#372;', '&#374', '&#373', '&#375;', '&sbquo;', '&#8219;', '&bdquo;', '&hellip;', '&trade;', '&#9658;', '&bull;',
    '&rarr;', '&rArr;', '&hArr;', '&diams;', '&asymp;',
  ];

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    $settings = $editor->getSettings();
    foreach ($settings['toolbar']['rows'] as $row) {
      foreach ($row as $group) {
        foreach ($group['items'] as $button) {
          if ($button === 'SpecialChar') {
            return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    // Defaults.
    $config = ['characters' => '', 'replace' => FALSE];
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['specialchars'])) {
      $config = $settings['plugins']['specialchars'];
    }

    $characters = explode("\n", $config['characters']);
    $characters = array_map('trim', $characters);
    $characters = array_filter($characters, 'strlen');

    // Not replace = append.
    if (!$config['replace']) {
      $characters = array_merge($this->defaultCharacters, $characters);
    }

    return [
      'specialChars' => $characters,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'SpecialChar' => [
        'label' => $this->t('Character map'),
        'image_alternative' => $this->buttonTemplate('special char'),
        'image_alternative_rtl' => $this->buttonTemplate('special char', 'rtl'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    // Defaults.
    $config = ['characters' => '', 'replace' => FALSE];
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['specialchars'])) {
      $config = $settings['plugins']['specialchars'];
    }

    $form['characters'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Special characters'),
      '#description' => $this->t('One per line'),
      '#default_value' => $config['characters'],
    ];

    $form['replace'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace default characters?'),
      '#description' => $this->t('Leaving un-checked will append to default character list.'),
      '#default_value' => $config['replace'],
    ];

    return $form;
  }

  /**
   * CKEditor button template.
   *
   * @param string $name
   *   Button name.
   * @param string $direction
   *   Language direction.
   *
   * @return array
   *   Renderable array.
   *
   * @see \Drupal\ckeditor\Plugin\CKEditorPlugin\Internal::getButtons()
   */
  protected function buttonTemplate($name, $direction = 'ltr') {
    // In the markup below, we mostly use the name (which may include spaces),
    // but in one spot we use it as a CSS class, so strip spaces.
    // Note: this uses str_replace() instead of Html::cleanCssIdentifier()
    // because we must provide these class names exactly how CKEditor expects
    // them in its library, which cleanCssIdentifier() does not do.
    $class_name = str_replace(' ', '', $name);
    return [
      '#type' => 'inline_template',
      '#template' => '<a href="#" class="cke-icon-only cke_{{ direction }}" role="button" title="{{ name }}" aria-label="{{ name }}"><span class="cke_button_icon cke_button__{{ classname }}_icon">{{ name }}</span></a>',
      '#context' => [
        'direction' => $direction,
        'name' => $name,
        'classname' => $class_name,
      ],
    ];
  }

}
