<?php

namespace Drupal\Tests\potion\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Cover default behaviors of translations.
 *
 * @group potion
 * @group potion_kernel
 * @group potion_kernel_utility
 */
class UtilityTest extends KernelTestBase {

  /**
   * The Utility service of Potion.
   *
   * @var \Drupal\potion\Utility
   */
  protected $utility;

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

    /** @var \Drupal\potion\Utility $utility */
    $this->utility = $this->container->get('potion.utility');
  }

  /**
   * @covers \Drupal\potion\Utility::sanitizePath
   * @dataProvider getTestSanitizePath
   */
  public function testSanitizePath($path, $expected) {
    $result = $this->utility->sanitizePath($path);
    $this->assertEquals($expected, $result);
  }

  /**
   * Provider of testSanitizePath.
   *
   * @return array
   *   Return an array of arrays.
   */
  public function getTestSanitizePath() {
    return [
      ['/foo/', '/foo/'],
      ['foo/', 'foo/'],
      ['bar', 'bar/'],
      ['foo/bar', 'foo/bar/'],
      ['temporary://', 'temporary://'],
      ['public://', 'public://'],
      ['public://foo', 'public://foo/'],
      ['public://foo/bar/', 'public://foo/bar/'],
    ];
  }

}
