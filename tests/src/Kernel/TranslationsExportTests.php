<?php

namespace Drupal\Tests\potion\Kernel;

use Drupal\potion\Exception\PotionException;

/**
 * @coversDefaultClass \Drupal\potion\TranslationsExport
 * @group potion
 * @group potion_kernel
 * @group potion_kernel_translations_export
 */
class TranslationsExportTests extends TranslationsTestsBase {

  /**
   * The Translation exporter.
   *
   * @var \Drupal\potion\TranslationsExport
   */
  protected $translationExport;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'locale',
    'language',
    'system',
    'potion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\potion\TranslationsExport $translationExport */
    $this->translationExport = $this->container->get('potion.translations.export');

    /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
    $this->fileSystem = $this->container->get('file_system');
  }

  /**
   * @covers \Drupal\potion\TranslationsExport::exportFromDatabase
   */
  public function testReportFormat() {
    $this->translationExport->exportFromDatabase('fr', 'temporary://');
    $report = $this->translationExport->getReport();

    $this->assertArraySubset(array_keys($report), ['translated', 'untranslated', 'strings']);
    $this->assertInternalType('integer', $report['translated']);
    $this->assertInternalType('integer', $report['untranslated']);
    $this->assertInternalType('array', $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExport::exportFromDatabase
   */
  public function testExportDestinationNotFound() {
    $this->setExpectedException(PotionException::class, "No such directory temporary://not-found");
    $this->translationExport->exportFromDatabase('fr', 'temporary://not-found');
  }

  /**
   * @covers \Drupal\potion\TranslationsExport::exportFromDatabase
   */
  public function testExportDestinationNotWritable() {
    $dest = 'temporary://not-writable';
    // Prepare a non writable directory.
    file_prepare_directory($dest, FILE_CREATE_DIRECTORY);
    @chmod($dest, 0000);

    $this->setExpectedException(PotionException::class, "The destination temporary://not-writable is not writable.");
    $this->translationExport->exportFromDatabase('fr', $dest);
  }

  /**
   * @covers \Drupal\potion\TranslationsExport::exportFromDatabase
   */
  public function testExportInvalidLangcode() {
    $this->setExpectedException(PotionException::class, "The langcode ru is not defined. Please create & enabled it before trying to use it.");
    $this->translationExport->exportFromDatabase('ru', 'temporary://');
  }

  /**
   * @covers \Drupal\potion\TranslationsExport::exportFromDatabase
   */
  public function testTranslationsExportUriDest() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $dest = $this->fileSystem->realpath('temporary://');
    $file = $dest . DIRECTORY_SEPARATOR . 'fr.po';
    @unlink($file);
    $this->assertFalse(file_exists($file));
    $this->translationExport->exportFromDatabase('fr', $dest);
    $this->assertTrue(file_exists($file));
  }

  /**
   * @covers \Drupal\potion\TranslationsExport::exportFromDatabase
   */
  public function testTranslationsExportPathDest() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    @unlink('temporary://fr.po');
    $this->assertFalse(file_exists('temporary://fr.po'));
    $this->translationExport->exportFromDatabase('fr', 'temporary://');
    $this->assertTrue(file_exists('temporary://fr.po'));
  }

  /**
   * @covers \Drupal\potion\TranslationsExport::exportFromDatabase
   */
  public function testTranslationsExportCustomized() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExport->exportFromDatabase('fr', 'temporary://', [
      'non-customized' => FALSE,
      'customized'     => TRUE,
      'untranslated'   => FALSE,
    ]);
    $report = $this->translationExport->getReport();
    $this->assertEquals(3, $report['translated']);
    $this->assertEquals(0, $report['untranslated']);
    $this->assertCount(3, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExport::exportFromDatabase
   */
  public function testTranslationsExportNonCustomized() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExport->exportFromDatabase('fr', 'temporary://', [
      'non-customized' => TRUE,
      'customized'     => FALSE,
      'untranslated'   => FALSE,
    ]);
    $report = $this->translationExport->getReport();
    $this->assertEquals(3, $report['translated']);
    $this->assertEquals(0, $report['untranslated']);
    $this->assertCount(3, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExport::exportFromDatabase
   */
  public function testTranslationsExportAllExceptedUntranslated() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExport->exportFromDatabase('fr', 'temporary://', [
      'non-customized' => TRUE,
      'customized'     => TRUE,
      'untranslated'   => FALSE,
    ]);
    $report = $this->translationExport->getReport();
    $this->assertEquals(7, $report['translated']);
    $this->assertEquals(0, $report['untranslated']);
    $this->assertCount(7, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExport::exportFromDatabase
   */
  public function testTranslationsExportUntranslated() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExport->exportFromDatabase('fr', 'temporary://', [
      'non-customized' => FALSE,
      'customized'     => FALSE,
      'untranslated'   => TRUE,
    ]);
    $report = $this->translationExport->getReport();
    $this->assertEquals(0, $report['translated']);
    $this->assertEquals(1, $report['untranslated']);
    $this->assertCount(1, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExport::exportFromDatabase
   */
  public function testTranslationsExportUntranslatedNonCustomized() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExport->exportFromDatabase('fr', 'temporary://', [
      'non-customized' => TRUE,
      'customized'     => FALSE,
      'untranslated'   => TRUE,
    ]);
    $report = $this->translationExport->getReport();
    $this->assertEquals(3, $report['translated']);
    $this->assertEquals(2, $report['untranslated']);
    $this->assertCount(5, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExport::exportFromDatabase
   */
  public function testTranslationsExportUntranslatedCustomized() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExport->exportFromDatabase('fr', 'temporary://', [
      'non-customized' => FALSE,
      'customized'     => TRUE,
      'untranslated'   => TRUE,
    ]);
    $report = $this->translationExport->getReport();
    $this->assertEquals(3, $report['translated']);
    $this->assertEquals(2, $report['untranslated']);
    $this->assertCount(5, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExport::exportFromDatabase
   */
  public function testTranslationsExportAll() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExport->exportFromDatabase('fr', 'temporary://', [
      'non-customized' => TRUE,
      'customized'     => TRUE,
      'untranslated'   => TRUE,
    ]);
    $report = $this->translationExport->getReport();
    $this->assertEquals(7, $report['translated']);
    $this->assertEquals(2, $report['untranslated']);
    $this->assertCount(9, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExport::exportFromDatabase
   */
  public function testTranslationsExportNone() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExport->exportFromDatabase('fr', 'temporary://', [
      'non-customized' => FALSE,
      'customized'     => FALSE,
      'untranslated'   => FALSE,
    ]);
    $report = $this->translationExport->getReport();
    $this->assertEquals(0, $report['translated']);
    $this->assertEquals(9, $report['untranslated']);
    $this->assertCount(9, $report['strings']);
  }
}