<?php

namespace Drupal\Tests\potion\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Gettext\PoItem;

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

  /**
   * @covers \Drupal\potion\Utility::setItem
   * @dataProvider getTestSetItem
   */
  public function testSetItem($msgid, $msgctxt, $msgstr, $expected) {
    $items = $this->utility->setItem($msgid, $msgctxt, $msgstr);

    $this->assertContainsOnlyInstancesOf(PoItem::class, $items);

    $item = reset($items);
    $this->assertEquals($item->getSource(), $expected['source']);
    $this->assertEquals($item->getContext(), $expected['context']);
    $this->assertEquals($item->getTranslation(), $expected['transaltion']);
  }

  /**
   * Provider of getTestSetItem.
   *
   * @return array
   *   Return an array of arrays.
   */
  public function getTestSetItem() {
    return [
      ['Hello moon!', NULL, [], [
        'source'      => 'Hello moon!',
        'context'     => '',
        'transaltion' => '',
      ],
      ],
      ['Hello moon!', 'Lolspeak', [], [
        'source'      => 'Hello moon!',
        'context'     => 'Lolspeak',
        'transaltion' => '',
      ],
      ],
      ['Hello moon!', NULL, 'Holla world!', [
        'source'      => 'Hello moon!',
        'context'     => '',
        'transaltion' => 'Holla world!',
      ],
      ],
      ['Hello moon!', 'Lolspeak', 'Holla world!', [
        'source'      => 'Hello moon!',
        'context'     => 'Lolspeak',
        'transaltion' => 'Holla world!',
      ],
      ],
      [['singular @count @foo', 'plural @count @foo'], NULL, [], [
        'source'      => ['singular @count @foo', 'plural @count @foo'],
        'context'     => '',
        'transaltion' => ['', ''],
      ],
      ],
      [['singular @count @foo', 'plural @count @foo'], 'Lolspeak', [], [
        'source'      => ['singular @count @foo', 'plural @count @foo'],
        'context'     => 'Lolspeak',
        'transaltion' => ['', ''],
      ],
      ],
      [['singular @count @foo', 'plural @count @foo'], NULL, ['singular @count foobar', 'plural @count foobar'], [
        'source'      => ['singular @count @foo', 'plural @count @foo'],
        'context'     => '',
        'transaltion' => ['singular @count foobar', 'plural @count foobar'],
      ],
      ],
      [['singular @count @foo', 'plural @count @foo'], 'Lolspeak', ['singular @count foobar', 'plural @count foobar'], [
        'source'      => ['singular @count @foo', 'plural @count @foo'],
        'context'     => 'Lolspeak',
        'transaltion' => ['singular @count foobar', 'plural @count foobar'],
      ],
      ],
    ];
  }

}
