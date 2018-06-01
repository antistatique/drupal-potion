<?php

namespace Drupal\Tests\potion\Unit;

/**
 * @coversDefaultClass \Drupal\potion\Utility
 * @group potion
 * @group potion_unit
 * @group potion_unit_utility
 */
class UtilityTest extends UtilityTestBase {

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
