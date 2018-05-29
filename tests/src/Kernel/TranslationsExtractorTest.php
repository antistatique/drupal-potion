<?php

namespace Drupal\Tests\potion\Kernel;

use Drupal\potion\Exception\PotionException;

/**
 * @coversDefaultClass \Drupal\potion\TranslationsExtractor
 *
 * @group potion
 * @group potion_kernel
 * @group potion_kernel_translations_extractor
 */
class TranslationsExtractorTest extends TranslationsTestsBase {

  /**
   * The Translation exporter.
   *
   * @var \Drupal\potion\TranslationsExtractor
   */
  protected $translationExtractor;

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
    'potion_test',
  ];

  /**
   * The directory of tests for twig extractions.
   *
   * @var array
   */
  protected $extractionPath;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\potion\TranslationsExtractor $translationExtractor */
    $this->translationExtractor = $this->container->get('potion.translations.extractor');

    /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
    $this->fileSystem = $this->container->get('file_system');

    /** @var string $extractionPath */
    $this->extractionPath = drupal_get_path('module', 'potion_test');
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testReportFormat() {
    $this->translationExtractor->extract('fr', $this->extractionPath, 'temporary://', TRUE);
    $report = $this->translationExtractor->getReport();

    $this->assertArraySubset(array_keys($report), [
      'twig',
      'php',
      'yaml',
      'strings',
    ]);
    $this->assertInternalType('integer', $report['twig']);
    $this->assertInternalType('integer', $report['php']);
    $this->assertInternalType('integer', $report['yaml']);
    $this->assertInternalType('array', $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testExtractDestinationNotFound() {
    $this->setExpectedException(PotionException::class, "No such file or directory temporary://not-found");
    $this->translationExtractor->extract('fr', $this->extractionPath, 'temporary://not-found', TRUE);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testExtractDestinationNotWritable() {
    $dest = 'temporary://not-writable';
    // Prepare a non writable directory.
    file_prepare_directory($dest, FILE_CREATE_DIRECTORY);
    @chmod($dest, 0000);

    $this->setExpectedException(PotionException::class, "The path temporary://not-writable is not writable.");
    $this->translationExtractor->extract('fr', $this->extractionPath, 'temporary://not-writable', TRUE);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testExtractSourceNotFound() {
    $this->setExpectedException(PotionException::class, "No such file or directory temporary://not-found");
    $this->translationExtractor->extract('fr', 'temporary://not-found', 'temporary://', TRUE);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testExtractSourceNotReadable() {
    $dest = 'temporary://not-readable';
    // Prepare a non readable directory.
    file_prepare_directory($dest, FILE_CREATE_DIRECTORY);
    @chmod($dest, 0000);

    $this->setExpectedException(PotionException::class, "The path temporary://not-readable is not readable.");
    $this->translationExtractor->extract('fr', 'temporary://not-readable', 'temporary://', TRUE);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testExtractInvalidLangcode() {
    $this->setExpectedException(PotionException::class, "The langcode ru is not defined. Please create & enabled it before trying to use it.");
    $this->translationExtractor->extract('ru', $this->extractionPath, 'temporary://', TRUE);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testTranslationsExportUriDest() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $dest = $this->fileSystem->realpath('temporary://');
    $file = $dest . DIRECTORY_SEPARATOR . 'fr.po';
    @unlink($file);
    $this->assertFalse(file_exists($file));
    $this->translationExtractor->extract('fr', $this->extractionPath, $dest, TRUE);
    $this->assertTrue(file_exists($file));
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testTranslationsExportPathDest() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    @unlink('temporary://fr.po');
    $this->assertFalse(file_exists('temporary://fr.po'));
    $this->translationExtractor->extract('fr', $this->extractionPath, 'temporary://', TRUE);
    $this->assertTrue(file_exists('temporary://fr.po'));
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testTranslationsExportAll() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, 'temporary://', TRUE, FALSE, [
      'exclude-yaml' => FALSE,
      'exclude-twig' => FALSE,
      'exclude-php'  => FALSE,
    ]);

    $report = $this->translationExtractor->getReport();
    $this->assertEquals(25, $report['twig']);
    $this->assertEquals(32, $report['php']);
    $this->assertEquals(7, $report['yaml']);
    $this->assertCount(64, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testTranslationsExportRecursivity() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, 'temporary://', FALSE, FALSE, [
      'exclude-yaml' => FALSE,
      'exclude-twig' => FALSE,
      'exclude-php'  => FALSE,
    ]);

    $report = $this->translationExtractor->getReport();
    $this->assertEquals(0, $report['twig']);
    $this->assertEquals(2, $report['php']);
    $this->assertEquals(7, $report['yaml']);
    $this->assertCount(9, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testTranslationsExportNone() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, 'temporary://', TRUE, FALSE, [
      'exclude-yaml' => TRUE,
      'exclude-twig' => TRUE,
      'exclude-php'  => TRUE,
    ]);

    $report = $this->translationExtractor->getReport();
    $this->assertEquals(0, $report['twig']);
    $this->assertEquals(0, $report['php']);
    $this->assertEquals(0, $report['yaml']);
    $this->assertCount(0, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testTranslationsExportTwigOnly() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, 'temporary://', TRUE, FALSE, [
      'exclude-yaml' => TRUE,
      'exclude-twig' => FALSE,
      'exclude-php'  => TRUE,
    ]);

    $report = $this->translationExtractor->getReport();
    $this->assertEquals(25, $report['twig']);
    $this->assertEquals(0, $report['php']);
    $this->assertEquals(0, $report['yaml']);
    $this->assertCount(25, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testTranslationsExportPhpOnly() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, 'temporary://', TRUE, FALSE, [
      'exclude-yaml' => TRUE,
      'exclude-twig' => TRUE,
      'exclude-php'  => FALSE,
    ]);

    $report = $this->translationExtractor->getReport();
    $this->assertEquals(0, $report['twig']);
    $this->assertEquals(32, $report['php']);
    $this->assertEquals(0, $report['yaml']);
    $this->assertCount(32, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testTranslationsExportYamlOnly() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, 'temporary://', TRUE, FALSE, [
      'exclude-yaml' => FALSE,
      'exclude-twig' => TRUE,
      'exclude-php'  => TRUE,
    ]);

    $report = $this->translationExtractor->getReport();
    $this->assertEquals(0, $report['twig']);
    $this->assertEquals(0, $report['php']);
    $this->assertEquals(7, $report['yaml']);
    $this->assertCount(7, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testTranslationsExportPhpTwigOnly() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, 'temporary://', TRUE, FALSE, [
      'exclude-yaml' => TRUE,
      'exclude-twig' => FALSE,
      'exclude-php'  => FALSE,
    ]);

    $report = $this->translationExtractor->getReport();
    $this->assertEquals(25, $report['twig']);
    $this->assertEquals(32, $report['php']);
    $this->assertEquals(0, $report['yaml']);
    $this->assertCount(57, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testTranslationsExportPhpYamlOnly() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, 'temporary://', TRUE, FALSE, [
      'exclude-yaml' => FALSE,
      'exclude-twig' => TRUE,
      'exclude-php'  => FALSE,
    ]);

    $report = $this->translationExtractor->getReport();
    $this->assertEquals(0, $report['twig']);
    $this->assertEquals(32, $report['php']);
    $this->assertEquals(7, $report['yaml']);
    $this->assertCount(39, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testTranslationsExportYamlTwigOnly() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, 'temporary://', TRUE, FALSE, [
      'exclude-yaml' => FALSE,
      'exclude-twig' => FALSE,
      'exclude-php'  => TRUE,
    ]);

    $report = $this->translationExtractor->getReport();
    $this->assertEquals(25, $report['twig']);
    $this->assertEquals(0, $report['php']);
    $this->assertEquals(7, $report['yaml']);
    $this->assertCount(32, $report['strings']);
  }

}
