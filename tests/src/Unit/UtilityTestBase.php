<?php

namespace Drupal\Tests\potion\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\potion\Utility;
use Drupal\potion\GettextWrapper;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Base class for Utility unit tests.
 */
abstract class UtilityTestBase extends UnitTestCase {

  /**
   * The Utility service of Potion.
   *
   * @var \Drupal\potion\Utility
   */
  protected $utility;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $en = new Language([
      'id'        => 'en',
      'name'      => 'English',
      'direction' => Language::DIRECTION_LTR,
      'weight'    => 0,
      'locked'    => FALSE,
    ]);

    $fr = new Language([
      'id'        => 'fr',
      'name'      => 'French',
      'direction' => Language::DIRECTION_LTR,
      'weight'    => 1,
      'locked'    => FALSE,
    ]);

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ProphecyInterface $config_factory */
    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    /** @var \Drupal\Core\Config\ImmutableConfig|\Prophecy\Prophecy\ProphecyInterface $config */
    $config = $this->prophesize(ImmutableConfig::class);
    $config_factory->get('potion.gettext.settings')
      ->willReturn($config->reveal());
    $config->get('path')
      ->willReturn('');

    /** @var \Drupal\Potion\GettextWrapper $gettext_wrapper */
    $gettext_wrapper = new GettextWrapper($config_factory->reveal());

    /** @var \Drupal\Core\Language\LanguageManagerInterface|\Prophecy\Prophecy\ProphecyInterface $language_manager */
    $language_manager = $this->prophesize(LanguageManagerInterface::class);

    /** @var \Drupal\Core\File\FileSystemInterface|\Prophecy\Prophecy\ProphecyInterface $file_system */
    $file_system = $this->prophesize(FileSystemInterface::class);

    $this->utility = new Utility($language_manager->reveal(), $gettext_wrapper, $file_system->reveal());
    $language_manager->getLanguages()
      ->willReturn(['en' => $en, 'fr' => $fr]);
  }

}
