<?php

namespace Drupal\Tests\potion\Kernel;

use Drupal\potion\Exception\PotionException;

/**
 * @coversDefaultClass \Drupal\potion\TranslationsImport
 * @group potion
 * @group potion_kernel
 * @group potion_kernel_translations_import
 */
class TranslationsImportTests extends TranslationsTestsBase {

  /**
   * The Translation importer.
   *
   * @var \Drupal\potion\TranslationsImport
   */
  protected $translationsImport;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
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
    $this->translationsImport = $this->container->get('potion.translations.import');
  }

  /**
   * Cover setup doesn't install translations by default.
   *
   * Default translations could result in FALSE positive into following tests.
   */
  public function testNoTranslationsOnSetup() {
    // Assert there is not translations in the database.
    $strings = $this->localStorage->getStrings([]);
    $this->assertEqual(count($strings), 0, 'Found 0 source strings in the database.');
    $translations = $this->localStorage->findTranslation([]);
    $this->assertEqual(count($translations), 0, 'Found 0 translations strings in the database.');
  }

  /**
   * @covers \Drupal\potion\TranslationsImport::importFromFile
   */
  public function testCustomizedImport() {
    $this->setUpTranslations();

    $source = $this->translationsPath . '/fr.po';
    $this->translationsImport->importFromFile('fr', $source, ['customized' => LOCALE_CUSTOMIZED, 'overwrite' => FALSE]);

    // Load all source strings.
    $strings = $this->localStorage->getStrings([]);
    $this->assertEqual(count($strings), 13, 'Found 13 source strings in the database.');

    // Existing "non-customized" source has not beed overrided.
    $source = $this->localStorage->findString(['source' => 'last year']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'l’année dernière');

    // Assert unexisting source w/ context is imported as "customized".
    $source = $this->localStorage->findString(['source' => 'Jul', 'context' => 'Abbreviated month name']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_CUSTOMIZED);
    $this->assertEqual($string->translation, 'Juil.', 'Successfully loaded translation by source and context.');

    // Existing "non-customized" trans w/o context has not been overrided.
    $source = $this->localStorage->findString(['source' => 'Jul']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'Juil.');

    // Existing "customized" trans w/o context has not been overrided.
    $source = $this->localStorage->findString(['source' => 'Jan']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'Janv.');

    // Assert strings with vars are imported as "customized".
    $source = $this->localStorage->findString(['source' => 'I love @color car']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_CUSTOMIZED);
    $this->assertEqual($string->translation, "J'adore les voitures @color", 'Successfully loaded translation with var(s).');

    // Assert plural forms are imported as "customized".
    $source = $this->localStorage->findString(['source' => '@count doctor@count doctors']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_CUSTOMIZED);
    $this->assertNotNull($string, 'Successfully loaded plural translation.');

    // Existing "non-customized" translations w/ context has not been overrided.
    $source = $this->localStorage->findString(['source' => 'March', 'context' => 'Long month name']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'Mars');

    // Existing "customized" translations w/ context has not been overrided.
    $source = $this->localStorage->findString(['source' => 'April', 'context' => 'Long month name']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'April');
  }

  /**
   * @covers \Drupal\potion\TranslationsImport::importFromFile
   */
  public function testNonCustomizedImport() {
    $this->setUpTranslations();

    $source = $this->translationsPath . '/fr.po';
    $this->translationsImport->importFromFile('fr', $source, ['customized' => LOCALE_NOT_CUSTOMIZED, 'overwrite' => FALSE]);

    // Load all source strings.
    $strings = $this->localStorage->getStrings([]);
    $this->assertEqual(count($strings), 13, 'Found 13 source strings in the database.');

    // Existing "non-customized" source has not beed overrided.
    $source = $this->localStorage->findString(['source' => 'last year']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'l’année dernière');

    // Assert unexisting source w/ context is imported as "customized".
    $source = $this->localStorage->findString(['source' => 'Jul', 'context' => 'Abbreviated month name']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertEqual($string->translation, 'Juil.', 'Successfully loaded translation by source and context.');

    // Existing "non-customized" trans w/o context has not been overrided.
    $source = $this->localStorage->findString(['source' => 'Jul']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'Juil.');

    // Existing "customized" trans w/o context has not been overrided.
    $source = $this->localStorage->findString(['source' => 'Jan']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'Janv.');

    // Assert strings with vars are imported as "non-customized".
    $source = $this->localStorage->findString(['source' => 'I love @color car']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertEqual($string->translation, "J'adore les voitures @color", 'Successfully loaded translation with var(s).');

    // Assert plural forms are imported as "non-customized".
    $source = $this->localStorage->findString(['source' => '@count doctor@count doctors']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertNotNull($string, 'Successfully loaded plural translation.');

    // Existing "non-customized" translations w/ context has not been overrided.
    $source = $this->localStorage->findString(['source' => 'March', 'context' => 'Long month name']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'Mars');

    // Existing "customized" translations w/ context has not been overrided.
    $source = $this->localStorage->findString(['source' => 'April', 'context' => 'Long month name']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'April');
  }

  /**
   * @covers \Drupal\potion\TranslationsImport::importFromFile
   */
  public function testCustomizedImportOverwrite() {
    $this->setUpTranslations();

    $source = $this->translationsPath . '/fr.po';
    $this->translationsImport->importFromFile('fr', $source, ['customized' => LOCALE_CUSTOMIZED, 'overwrite' => TRUE]);

    // Load all source strings.
    $strings = $this->localStorage->getStrings([]);
    $this->assertEqual(count($strings), 13, 'Found 13 source strings in the database.');

    // Existing "non-customized" source has not beed overrided.
    $source = $this->localStorage->findString(['source' => 'last year']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'l’année dernière');

    // Assert unexisting source w/ context is imported as "customized".
    $source = $this->localStorage->findString(['source' => 'Jul', 'context' => 'Abbreviated month name']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_CUSTOMIZED);
    $this->assertEqual($string->translation, 'Juil.', 'Successfully loaded translation by source and context.');

    // Existing "non-customized" trans w/o context has not been overrided.
    $source = $this->localStorage->findString(['source' => 'Jul']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'Juil.');

    // Existing "customized" trans w/o context has not been overrided.
    $source = $this->localStorage->findString(['source' => 'Jan']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'Janv.');

    // Assert strings with vars are imported as "customized".
    $source = $this->localStorage->findString(['source' => 'I love @color car']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_CUSTOMIZED);
    $this->assertEqual($string->translation, "J'adore les voitures @color", 'Successfully loaded translation with var(s).');

    // Assert plural forms are imported as "customized".
    $source = $this->localStorage->findString(['source' => '@count doctor@count doctors']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_CUSTOMIZED);
    $this->assertNotNull($string, 'Successfully loaded plural translation.');

    // Existing "non-customized" translations w/ context has not been overrided.
    $source = $this->localStorage->findString(['source' => 'March', 'context' => 'Long month name']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'Mars');

    // Existing "customized" translations w/ context has been overrided.
    $source = $this->localStorage->findString(['source' => 'April', 'context' => 'Long month name']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_CUSTOMIZED);
    $this->assertEqual($string->translation, 'April');
  }

  /**
   * @covers \Drupal\potion\TranslationsImport::importFromFile
   */
  public function testNonCustomizedImportOverwrite() {
    $this->setUpTranslations();

    $source = $this->translationsPath . '/fr.po';
    $this->translationsImport->importFromFile('fr', $source, ['customized' => LOCALE_NOT_CUSTOMIZED, 'overwrite' => TRUE]);

    // Existing "non-customized" source has not beed overrided.
    $source = $this->localStorage->findString(['source' => 'last year']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertEqual($string->translation, 'l’année dernière');

    // Assert unexisting source w/ context is imported as "non-customized".
    $source = $this->localStorage->findString(['source' => 'Jul', 'context' => 'Abbreviated month name']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertEqual($string->translation, 'Juil.', 'Successfully loaded translation by source and context.');

    // Existing "non-customized" trans w/o context has not been overrided.
    $source = $this->localStorage->findString(['source' => 'Jul']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'Juil.');

    // Existing "customized" trans w/o context has not been overrided.
    $source = $this->localStorage->findString(['source' => 'Jan']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'Janv.');

    // Assert strings with vars are imported as "non-customized".
    $source = $this->localStorage->findString(['source' => 'I love @color car']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertEqual($string->translation, "J'adore les voitures @color", 'Successfully loaded translation with var(s).');

    // Assert plural forms are imported as "non-customized".
    $source = $this->localStorage->findString(['source' => '@count doctor@count doctors']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertNotNull($string, 'Successfully loaded plural translation.');

    // Existing "non-customized" translations w/ context has been overrided.
    $source = $this->localStorage->findString(['source' => 'March', 'context' => 'Long month name']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_NOT_CUSTOMIZED);
    $this->assertEqual($string->translation, 'Mars');

    // Existing "customized" translations w/ context has not been overrided.
    $source = $this->localStorage->findString(['source' => 'April', 'context' => 'Long month name']);
    $string = $this->localStorage->findTranslation(['language' => 'fr', 'lid' => $source->lid]);
    $this->assertEqual($string->customized, LOCALE_CUSTOMIZED);
    $this->assertNotEqual($string->translation, 'April');
  }

  /**
   * @covers \Drupal\potion\TranslationsImport::importFromFile
   */
  public function testInvalidPo() {
    $this->setExpectedException(PotionException::class, "File modules/contrib/potion/tests/modules/potion_test/assets/malformed/missing-msgid.po is a malformed .po file.");

    $source = $this->translationsPath . '/malformed/missing-msgid.po';
    $this->translationsImport->importFromFile('fr', $source);
  }

  /**
   * @covers \Drupal\potion\TranslationsImport::importFromFile
   */
  public function testInvalidLangcode() {
    $this->setExpectedException(PotionException::class, "The langcode ru is not defined. Please create & enabled it before trying to use it.");

    $source = $this->translationsPath . '/fr.po';
    $this->translationsImport->importFromFile('ru', $source);
  }

}
