<?php

namespace Drupal\classy_paragraphs\Tests;

use Drupal\Tests\BrowserTestBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;

/**
 * Tests the classy_paragraphs_test module.
 *
 * @group classy_paragraphs
 */
class ClassyParagraphsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['classy_paragraphs', 'node', 'classy_paragraphs_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(['access content',
      'access administration pages', 'administer site configuration',
      'administer nodes', 'administer paragraphs types', 'administer content types']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Creates a populated node with a text paragraph.
   *
   * @param $styles
   * @param string $node_type
   *
   * @return array
   *   A node object.
   */
  public function createNodeWithParagraph($styles, $node_type = 'cp_test') {
    $values['type'] = 'cp_test_text';
    $values['field_cp_test_body'] = $this->randomString();
    $values['field_cp_test_classes'] = $styles;
    $paragraph = Paragraph::create($values);
    $paragraph->save();

    $node = Node::create([
      'type' => $node_type,
      'title' => $this->randomString(),
      'uid' => 0,
      'status' => 1,
      'field_cp_test_paragraphs' => [
        [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ],
      ],
    ]);
    $node->save();

    return [$node];
  }

  /**
   * Assert that the configuration link appears.
   */
  public function testStyleConfigurationPage() {
    $this->drupalGet('admin/structure/classy_paragraphs_style');
    $this->assertText('Classy paragraphs style');
  }

  /**
   * Assert that the CP test modules has been enabled.
   */
  public function testClassyParagraphsTestModule() {
    // Check the test text paragraph type.
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->assertText('Text (CP Test)');

    // Check the test content type type.
    $this->drupalGet('admin/structure/types');
    $this->assertText('CP Test');
  }

  /**
   * Test the appearance of a single style.
   */
  public function testClassyParagraphsCheckSingleStyle() {
    $style = ['cp_test_loud'];
    $style_css = 'loud-background';
    /** @var \Drupal\node\Entity\Node $node */
    list ($node) = $this->createNodeWithParagraph($style);

    $this->drupalGet('node/' . $node->id());
    $class = $this->xpath('//*[contains(@class, "' . $style_css . '")]');
    $this->assertTrue(count($class) == 1, $style_css . ' class found.');
  }
}
