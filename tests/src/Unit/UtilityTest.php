<?php

namespace Drupal\Tests\potion\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\potion\Utility;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\Language;

/**
 * @coversDefaultClass \Drupal\potion\Utility
 * @group potion
 * @group potion_unit
 * @group potion_unit_utility
 */
class UtilityTest extends UnitTestCase {

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

    /** @var \Drupal\Core\Language\LanguageManagerInterface|\Prophecy\Prophecy\ProphecyInterface $language_manager */
    $language_manager = $this->prophesize(LanguageManagerInterface::class);
    $this->utility = new Utility($language_manager->reveal());
    $language_manager->getLanguages()
      ->willReturn(['en' => $en, 'fr' => $fr]);
  }

  /**
   * @covers \Drupal\potion\Utility::isValidPo
   * @dataProvider getTestIsValidPo
   */
  public function testIsValidPo($filepath, $expected) {
    $result = $this->utility->isValidPo($filepath);
    $this->assertEquals($result, $expected);
  }

  /**
   * Provider of testIsValidPo.
   *
   * @return array
   *   Return an array of arrays.
   */
  public function getTestIsValidPo() {
    $dir = __DIR__ . '/../../modules/potion_test/assets';
    $dir_malformed = $dir . '/malformed';

    return [
      [$dir, FALSE],
      [$dir_malformed, FALSE],
      [$dir . '/en.po', FALSE],
      [$dir . '/fr.po', TRUE],
      [$dir . '/de.po', TRUE],
      [$dir_malformed . '/missing-msgid.po', FALSE],
      [$dir_malformed . '/missing-msgstr.po', FALSE],
      [$dir_malformed . '/missing-header.po', FALSE],
      [$dir_malformed . '/quote.po', FALSE],
    ];
  }

  /**
   * @covers \Drupal\potion\Utility::isLangcodeEnabled
   * @dataProvider getTestIsLangcodeEnabled
   */
  public function testIsLangcodeEnabled($langcode, $expected) {
    $result = $this->utility->isLangcodeEnabled($langcode);
    $this->assertEquals($result, $expected);
  }

  /**
   * Provider of testIsLangcodeEnabled.
   *
   * @return array
   *   Return an array of arrays.
   */
  public function getTestIsLangcodeEnabled() {
    return [
      ['fr', TRUE],
      ['fr-ch', FALSE],
      ['de', FALSE],
      ['de-ch', FALSE],
      ['en', TRUE],
    ];
  }

}
