<?php

namespace Drupal\Tests\potion\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

abstract class TranslationsTestsBase extends KernelTestBase {

  /**
   * The directory of tests .po files.
   *
   * @var array
   */
  protected $translationsPath;

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

    $this->installSchema('locale', ['locales_location', 'locales_source', 'locales_target']);

    $this->translationsPath = drupal_get_path('module', 'potion_test') . DIRECTORY_SEPARATOR . 'assets';
    $this->setUpLanguages();

    /** @var \Drupal\locale\StringStorageInterface $localStorage */
    $this->localStorage = $this->container->get('locale.storage');
  }

  /**
   * Sets up languages needed for this test.
   */
  protected function setUpLanguages() {
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('de')->save();
  }

  protected function setUpTranslations() {
    $source1 = $this->localStorage->createString([
      'source' => 'last year',
    ]);
    $source1->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source1->lid,
      'language'    => 'fr',
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_NOT_CUSTOMIZED
    ])->save();

    $source2 = $this->localStorage->createString([
      'source' => 'Yesterday',
    ]);
    $source2->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source2->lid,
      'language'    => 'fr',
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_CUSTOMIZED
    ])->save();

    $source3 = $this->localStorage->createString([
      'source'  => 'March',
      'context' => 'Long month name',
    ]);
    $source3->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source3->lid,
      'language'    => 'fr',
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_NOT_CUSTOMIZED
    ])->save();

    $source4 = $this->localStorage->createString([
      'source'  => 'April',
      'context' => 'Long month name',
    ]);
    $source4->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source4->lid,
      'language'    => 'fr',
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_CUSTOMIZED
    ])->save();

    $source5 = $this->localStorage->createString([
      'source'  => 'Jul',
    ]);
    $source5->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source5->lid,
      'language'    => 'fr',
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_NOT_CUSTOMIZED
    ])->save();

    $source6 = $this->localStorage->createString([
      'source'  => 'Jan',
    ]);
    $source6->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source6->lid,
      'language'    => 'fr',
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_CUSTOMIZED
    ])->save();

    $source7 = $this->localStorage->createString([
      'source' => 'bicycle',
    ]);
    $source7->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source7->lid,
      'language'    => 'fr',
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_CUSTOMIZED
    ])->save();

    $source8 = $this->localStorage->createString([
      'source' => 'car',
    ]);
    $source8->save();
    $this->translationsStrings[] = $this->localStorage->createTranslation([
      'lid'         => $source8->lid,
      'language'    => 'fr',
      'translation' => $this->randomMachineName(20),
      'customized'  => LOCALE_NOT_CUSTOMIZED
    ])->save();
  }

}