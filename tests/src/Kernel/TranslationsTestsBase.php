<?php

namespace Drupal\Tests\potion\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Base class for Translations kernel integration tests.
 */
abstract class TranslationsTestsBase extends KernelTestBase {

  /**
   * Collection of tests translations strings.
   *
   * @var \Drupal\locale\TranslationString[]
   */
  protected $translationsStrings;

  /**
   * String translation storage object.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $localStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('locale', [
      'locales_location',
      'locales_source',
      'locales_target',
    ]);

    $this->setUpLanguages();

    /** @var \Drupal\locale\StringStorageInterface $localStorage */
    $this->localStorage = $this->container->get('locale.storage');
  }

  /**
   * Sets up languages needed for test.
   */
  protected function setUpLanguages() {
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('de')->save();
  }

  /**
   * Sets up translations strings needed for test.
   */
  protected function setUpTranslations($langcode = 'fr') {
    $source1 = $this->localStorage->createString([
      'source' => 'last year',
    ]);
    $source1->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source1->lid,
      'language'    => $langcode,
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_NOT_CUSTOMIZED,
    ])->save();

    $source2 = $this->localStorage->createString([
      'source' => 'Yesterday',
    ]);
    $source2->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source2->lid,
      'language'    => $langcode,
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_CUSTOMIZED,
    ])->save();

    $source3 = $this->localStorage->createString([
      'source'  => 'March',
      'context' => 'Long month name',
    ]);
    $source3->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source3->lid,
      'language'    => $langcode,
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_NOT_CUSTOMIZED,
    ])->save();

    $source4 = $this->localStorage->createString([
      'source'  => 'April',
      'context' => 'Long month name',
    ]);
    $source4->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source4->lid,
      'language'    => $langcode,
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_CUSTOMIZED,
    ])->save();

    $source5 = $this->localStorage->createString([
      'source'  => 'Jul',
    ]);
    $source5->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source5->lid,
      'language'    => $langcode,
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_NOT_CUSTOMIZED,
    ])->save();

    $source6 = $this->localStorage->createString([
      'source'  => 'Jan',
    ]);
    $source6->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source6->lid,
      'language'    => $langcode,
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_CUSTOMIZED,
    ])->save();

    $source7 = $this->localStorage->createString([
      'source' => 'bicycle',
    ]);
    $source7->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source7->lid,
      'language'    => $langcode,
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_CUSTOMIZED,
    ])->save();

    $source8 = $this->localStorage->createString([
      'source' => 'car',
    ]);
    $source8->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source8->lid,
      'language'    => $langcode,
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_NOT_CUSTOMIZED,
    ])->save();
  }

  /**
   * Sets up unstranslated strings needed for test.
   */
  protected function setUpNonTranslations() {
    $source1 = $this->localStorage->createString([
      'source' => 'submarin',
    ]);
    $source1->save();
    $source2 = $this->localStorage->createString([
      'source' => 'yellow',
    ]);
    $source2->save();
    $source3 = $this->localStorage->createString([
      'source' => 'Alone in the dark.',
    ]);
    $source3->save();
  }

}
