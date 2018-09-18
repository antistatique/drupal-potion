<?php

namespace Drupal\Tests\potion\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\potion\MessageCatalogue;
use Drupal\Component\Gettext\PoItem;

/**
 * @coversDefaultClass \Drupal\potion\MessageCatalogue
 * @group potion
 * @group potion_kernel
 * @group potion_kernel_catalogue
 */
class MessageCatalogueTest extends KernelTestBase {

  /**
   * The catalogue of messages.
   *
   * @var \Drupal\potion\MessageCatalogue
   */
  protected $catalogue;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->catalogue = new MessageCatalogue();
  }

  /**
   * @covers \Drupal\potion\MessageCatalogue::add
   * @dataProvider getTestAdd
   */
  public function testAdd($msgid, $msgctxt, $msgstr, $expected) {
    $this->catalogue->add($msgid, $msgctxt, $msgstr);

    $items = $this->catalogue->all();
    $this->assertContainsOnlyInstancesOf(PoItem::class, $items);

    $item = reset($items);
    $this->assertEquals($item->getSource(), $expected['source']);
    $this->assertEquals($item->getContext(), $expected['context']);
    $this->assertEquals($item->getTranslation(), $expected['transaltion']);
  }

  /**
   * Provider of getTestAdd.
   *
   * @return array
   *   Return an array of arrays.
   */
  public function getTestAdd() {
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
