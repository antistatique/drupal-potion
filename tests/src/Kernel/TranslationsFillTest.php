<?php

namespace Drupal\Tests\potion\Kernel;

use Drupal\potion\Exception\PotionException;

/**
 * @coversDefaultClass \Drupal\potion\TranslationsFill
 *
 * @group potion
 * @group potion_kernel
 * @group potion_kernel_translations_fill
 */
class TranslationsFillTest extends TranslationsTestsBase {

  /**
   * The directory of tests .po files.
   *
   * @var array
   */
  protected $translationsPath;

  /**
   * The Translation filler.
   *
   * @var \Drupal\potion\TranslationsFill
   */
  protected $translationsFill;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'locale',
    'language',
    'file',
    'potion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\potion\TranslationsImport $translationsImport */
    $this->translationsFill = $this->container->get('potion.translations.fill');

    /** @var string $translationsPath */
    $this->translationsPath = drupal_get_path('module', 'potion_test') . DIRECTORY_SEPARATOR . 'assets';
  }

  /**
   * @covers \Drupal\potion\TranslationsFill::fillFromDatabase
   */
  public function testSourceNotFound() {
    $this->setExpectedException(PotionException::class, "No such file or directory temporary://not-found");
    $this->translationsFill->fillFromDatabase('fr', 'temporary://not-found');
  }

  /**
   * @covers \Drupal\potion\TranslationsFill::fillFromDatabase
   */
  public function testInvalidPo() {
    $this->setExpectedException(PotionException::class, "File modules/contrib/potion/tests/modules/potion_test/assets/malformed/missing-msgid.po is a malformed .po file.");

    $source = $this->translationsPath . '/malformed/missing-msgid.po';
    $this->translationsFill->fillFromDatabase('fr', $source);
  }

  /**
   * @covers \Drupal\potion\TranslationsFill::fillFromDatabase
   */
  public function testInvalidLangcode() {
    $this->setExpectedException(PotionException::class, "The langcode ru is not defined. Please create & enabled it before trying to use it.");

    $source = $this->translationsPath . '/fr.po';
    $this->translationsFill->fillFromDatabase('ru', $source);
  }

  /**
   * @covers \Drupal\potion\TranslationsFill::fillFromDatabase
   */
  public function testReportFormat() {
    $source = $this->translationsPath . '/fr.po';
    $this->translationsFill->fillFromDatabase('fr', $source);
    $report = $this->translationsFill->getReport();

    $this->assertInternalType('integer', $report['translated']);
    $this->assertInternalType('integer', $report['untranslated']);
    $this->assertInternalType('array', $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsFill::fillFromDatabase
   */
  public function testReturnTemporaryFile() {
    $source = $this->translationsPath . '/fr.po';
    $file = $this->translationsFill->fillFromDatabase('fr', $source);
    $this->assertInstanceOf('SplFileInfo', $file);
  }

  /**
   * @covers \Drupal\potion\TranslationsFill::fillFromDatabase
   */
  public function testFillComplete() {
    $this->setUpTranslations();

    $source = $this->translationsPath . '/fr.po';
    $this->translationsFill->fillFromDatabase('fr', $source);
    $report = $this->translationsFill->getReport();

    $this->assertEquals(9, $report['translated']);
    $this->assertEquals(1, $report['untranslated']);
    $this->assertCount(10, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsFill::fillFromDatabase
   */
  public function testFillCompleteWithNonTranslation() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $source = $this->translationsPath . '/fr.po';
    $this->translationsFill->fillFromDatabase('fr', $source);
    $report = $this->translationsFill->getReport();

    $this->assertEquals(9, $report['translated']);
    $this->assertEquals(1, $report['untranslated']);
    $this->assertCount(10, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsFill::fillFromDatabase
   */
  public function testFillPartial() {
    $this->setUpTranslations();

    $source = $this->translationsPath . '/fill/fr.po';
    $this->translationsFill->fillFromDatabase('fr', $source);
    $report = $this->translationsFill->getReport();

    $this->assertEquals(8, $report['translated']);
    $this->assertEquals(2, $report['untranslated']);
    $this->assertCount(10, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsFill::fillFromDatabase
   */
  public function testFillPartialWithNonTranslation() {
    $this->setUpTranslations();
    $this->setUpNonTranslations();

    $source = $this->translationsPath . '/fill/fr.po';
    $this->translationsFill->fillFromDatabase('fr', $source);
    $report = $this->translationsFill->getReport();

    $this->assertEquals(8, $report['translated']);
    $this->assertEquals(2, $report['untranslated']);
    $this->assertCount(10, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsFill::fillFromDatabase
   */
  public function testFillOverwrite() {
    $source = $this->translationsPath . '/fr.po';
    $this->translationsFill->fillFromDatabase('fr', $source, TRUE);
    $report = $this->translationsFill->getReport();

    $this->assertEquals(9, $report['translated']);
    $this->assertEquals(1, $report['untranslated']);
    $this->assertCount(10, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsFill::fillFromDatabase
   */
  public function testFillOverwritePartial() {
    $this->setUpTranslations();

    $source = $this->translationsPath . '/fill/fr.po';
    $this->translationsFill->fillFromDatabase('fr', $source, TRUE);
    $report = $this->translationsFill->getReport();

    $this->assertEquals(8, $report['translated']);
    $this->assertEquals(2, $report['untranslated']);
    $this->assertCount(10, $report['strings']);
  }

  /**
   * @covers \Drupal\potion\TranslationsFill::fillFromDatabase
   */
  public function testFillPartialWithNothing() {
    $source = $this->translationsPath . '/fill/fr.po';
    $this->translationsFill->fillFromDatabase('fr', $source);
    $report = $this->translationsFill->getReport();

    $this->assertEquals(5, $report['translated']);
    $this->assertEquals(5, $report['untranslated']);
    $this->assertCount(10, $report['strings']);
  }

}
