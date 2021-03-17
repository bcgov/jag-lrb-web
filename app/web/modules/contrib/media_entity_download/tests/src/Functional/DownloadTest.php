<?php

namespace Drupal\Tests\media_entity_download\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\user\Entity\Role;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group media_entity_download
 */
class DownloadTest extends BrowserTestBase {

  use MediaTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'node',
    'media',
    'file',
    'media_entity_download',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Default testing media type.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  private $defaultMediaType;

  /**
   * Field definition of default testing media type source field.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  private $defaultSourceField;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create bundle and modify form display.
    $this->defaultMediaType = $this->createMediaType('file', ['id' => 'testing']);
    $this->defaultSourceField = $this->defaultMediaType->getSource()->getSourceFieldDefinition($this->defaultMediaType);
  }

  /**
   * Test media entity creation.
   *
   * @param bool $published
   *   Published state of media entity.
   * @param \Drupal\Core\Session\AccountInterface $owner
   *   Media entity owner.
   *
   * @return \Drupal\media\Entity\Media
   *   The created media entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createMediaEntity($published, AccountInterface $owner) {
    $value = FileItem::generateSampleValue($this->defaultSourceField);
    $media = Media::create([
      'name' => 'test',
      'bundle' => 'testing',
      $this->defaultSourceField->getName() => $value['target_id'],
      'status' => $published,
    ]);
    $media->setOwnerId($owner->id());
    $media->save();
    return $media;
  }

  /**
   * Tests download of media entity files for anonymous users.
   */
  public function testMediaDownloadByAnonymous() {
    $media_owner = $this->createUser();
    $media = $this->createMediaEntity(TRUE, $media_owner);
    $media_unpublished = $this->createMediaEntity(FALSE, $media_owner);
    $this->assertTrue($media->isPublished());
    $this->assertFalse($media_unpublished->isPublished());

    $anonymous_role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $this->assertTrue(in_array('view media', $anonymous_role->getPermissions()));
    $anonymous_role->grantPermission('download media');
    $anonymous_role->save();

    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    $this->drupalGet(Url::fromRoute('media_entity_download.download', ['media' => $media->id()]));
    $this->assertEquals(Response::HTTP_OK, $this->getSession()->getStatusCode());

    $this->drupalGet(Url::fromRoute('media_entity_download.download', ['media' => $media_unpublished->id()]));
    $this->assertEquals(Response::HTTP_FORBIDDEN, $this->getSession()->getStatusCode());

    $anonymous_role->revokePermission('download media');
    $anonymous_role->save();

    $this->drupalGet(Url::fromRoute('media_entity_download.download', ['media' => $media->id()]));
    $this->assertEquals(Response::HTTP_FORBIDDEN, $this->getSession()->getStatusCode());

    $anonymous_role->grantPermission('download media');
    $anonymous_role->revokePermission('view media');
    $anonymous_role->save();

    $this->drupalGet(Url::fromRoute('media_entity_download.download', ['media' => $media->id()]));
    $this->assertEquals(Response::HTTP_FORBIDDEN, $this->getSession()->getStatusCode());

    $this->drupalGet(Url::fromRoute('media_entity_download.download', ['media' => $media_unpublished->id()]));
    $this->assertEquals(Response::HTTP_FORBIDDEN, $this->getSession()->getStatusCode());
  }

  /**
   * Tests download of media entity files by it's owner.
   */
  public function testMediaDownloadByOwner() {
    $media_owner = $this->createUser(['download media', 'view media']);
    $media_owner_role = Role::load(current($media_owner->getRoles(TRUE)));
    $no_media_owner = $this->createUser([
      'download media',
      'view media',
      'view own unpublished media',
    ]);
    $no_media_owner_role = Role::load(current($no_media_owner->getRoles(TRUE)));
    $this->assertNotEqual($media_owner_role, $no_media_owner_role);
    $media = $this->createMediaEntity(TRUE, $media_owner);
    $unpublished_media = $this->createMediaEntity(FALSE, $media_owner);

    $this->drupalLogin($media_owner);

    $this->drupalGet(Url::fromRoute('media_entity_download.download', ['media' => $media->id()]));
    $this->assertEquals(Response::HTTP_OK, $this->getSession()->getStatusCode());

    $this->drupalGet(Url::fromRoute('media_entity_download.download', ['media' => $unpublished_media->id()]));
    $this->assertEquals(Response::HTTP_FORBIDDEN, $this->getSession()->getStatusCode());

    $media_owner_role->grantPermission('view own unpublished media');
    $media_owner_role->save();

    $this->drupalGet(Url::fromRoute('media_entity_download.download', ['media' => $unpublished_media->id()]));
    $this->assertEquals(Response::HTTP_OK, $this->getSession()->getStatusCode());
    // Assert file headers.
    $unpublished_media_file_id = $unpublished_media->getSource()->getSourceFieldValue($unpublished_media);
    /** @var \Drupal\file\FileInterface $unpublished_media_file */
    $unpublished_media_file = File::load($unpublished_media_file_id);
    $this->assertEquals('attachment; filename="' . $unpublished_media_file->getFilename() . '"', $this->drupalGetHeader('Content-Disposition'));
    $this->assertEquals('public', $this->drupalGetHeader('Cache-Control'));
    $this->assertEquals($unpublished_media_file->getSize(), $this->drupalGetHeader('Content-Length'));

    $this->drupalLogin($no_media_owner);

    $this->drupalGet(Url::fromRoute('media_entity_download.download', ['media' => $media->id()]));
    $this->assertEquals(Response::HTTP_OK, $this->getSession()->getStatusCode());

    $this->drupalGet(Url::fromRoute('media_entity_download.download', ['media' => $unpublished_media->id()]));
    $this->assertEquals(Response::HTTP_FORBIDDEN, $this->getSession()->getStatusCode());
  }

  /**
   * Tests if attachment download option created correct HTTP response headers.
   */
  public function testAttachmentDownload() {
    $media_owner = $this->createUser([
      'download media',
    ]);
    $media = $this->createMediaEntity(TRUE, $media_owner);
    $this->drupalLogin($media_owner);

    $this->drupalGet(Url::fromRoute('media_entity_download.download',
      ['media' => $media->id()],
      ['query' => [ResponseHeaderBag::DISPOSITION_ATTACHMENT => '']]
    ));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->responseHeaderContains('Content-Disposition', ResponseHeaderBag::DISPOSITION_ATTACHMENT);

    $this->drupalGet(Url::fromRoute('media_entity_download.download',
      ['media' => $media->id()],
      ['query' => [ResponseHeaderBag::DISPOSITION_INLINE => '']]
    ));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->responseHeaderContains('Content-Disposition', ResponseHeaderBag::DISPOSITION_INLINE);

    $this->drupalGet(Url::fromRoute('media_entity_download.download',
      ['media' => $media->id()]
    ));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
    $this->assertSession()->responseHeaderContains('Content-Disposition', ResponseHeaderBag::DISPOSITION_ATTACHMENT);
  }

}
