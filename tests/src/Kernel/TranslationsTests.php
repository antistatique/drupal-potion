<?php

namespace Drupal\Tests\potion\Kernel;

use Drupal\potion\Exception\PotionException;

/**
 * Cover default behaviors of translations.
 *
 * @group potion
 * @group potion_kernel
 * @group potion_kernel_translations
 */
class TranslationsTests extends TranslationsTestsBase {

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
}