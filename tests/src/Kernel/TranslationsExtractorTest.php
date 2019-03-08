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
   * The Translation Extracter.
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
    $this->translationExtractor->extract('fr', $this->extractionPath, TRUE);
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
  public function testExtractorReturnTemporaryFile() {
    $file = $this->translationExtractor->extract('fr', $this->extractionPath, TRUE);
    $this->assertInstanceOf('SplFileInfo', $file);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testExtractorReturnNull() {
    $dest = 'temporary://empty';
    // Prepare a non readable directory.
    $this->fileSystem->prepareDirectory($dest, FILE_CREATE_DIRECTORY);

    $file = $this->translationExtractor->extract('fr', 'temporary://empty', TRUE);
    $this->assertNull($file);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testExtractSourceNotFound() {
    $this->setExpectedException(PotionException::class, "No such file or directory temporary://not-found");
    $this->translationExtractor->extract('fr', 'temporary://not-found', TRUE);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testExtractSourceNotReadable() {
    $dest = 'temporary://not-readable';
    // Prepare a non readable directory.
    $this->fileSystem->prepareDirectory($dest, FILE_CREATE_DIRECTORY);
    @chmod($dest, 0000);

    $this->setExpectedException(PotionException::class, "The path temporary://not-readable is not readable.");
    $this->translationExtractor->extract('fr', 'temporary://not-readable', TRUE);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testExtractInvalidLangcode() {
    $this->setExpectedException(PotionException::class, "The langcode ru is not defined. Please create & enabled it before trying to use it.");
    $this->translationExtractor->extract('ru', $this->extractionPath, TRUE);
  }

  /**
   * @covers \Drupal\potion\TranslationsExtractor::extract
   */
  public function testTranslationsExtractAll() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, TRUE, [
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
  public function testTranslationsExtractRecursivity() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, FALSE, [
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
  public function testTranslationsExtractNone() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, TRUE, [
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
  public function testTranslationsExtractTwigOnly() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, TRUE, [
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
  public function testTranslationsExtractPhpOnly() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, TRUE, [
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
  public function testTranslationsExtractYamlOnly() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, TRUE, [
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
  public function testTranslationsExtractPhpTwigOnly() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, TRUE, [
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
  public function testTranslationsExtractPhpYamlOnly() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, TRUE, [
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
  public function testTranslationsExtractYamlTwigOnly() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $this->translationExtractor->extract('fr', $this->extractionPath, TRUE, [
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
