<?php

namespace Drupal\potion\tests\Kernel\Extractor;

use Drupal\KernelTests\KernelTestBase;
use Drupal\potion\Extractor\TwigExtractor;
use Drupal\Component\Gettext\PoItem;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\potion\MessageCatalogue;

/**
 * @coversDefaultClass \Drupal\potion\Extractor\TwigExtractor
 * @group potion
 * @group potion_kernel
 * @group potion_kernel_extractor_twig
 */
class TwigExtractorTest extends KernelTestBase {

  public static $modules = [
    'locale',
    'language',
    'potion',
    'potion_test',
  ];

  /**
   * The directory of tests for twig extractions.
   *
   * @var array
   */
  protected $extractionPath;

  /**
   * The Twig environment loaded with the sandbox extension.
   *
   * @var \Twig_Environment
   */
  private $twig;

  /**
   * Collection of expected extracted translantions strings.
   *
   * Collection of poItems that should be generated when using the extractor
   * twig-only on the potion_tests/templates dir.
   *
   * @var \Drupal\Component\Gettext\PoItem[]
   */
  private $poItems;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Twig_Environment $twig */
    $this->twig = $this->container->get('twig');

    /** @var string $extractionPath */
    $this->extractionPath = drupal_get_path('module', 'potion_test') . DIRECTORY_SEPARATOR . 'templates';

    $po_items = [
      [
        'source' => 'foo.t',
        'context' => NULL,
      ],
      [
        'source' => 'foo.trans',
        'context' => NULL,
      ],
      [
        'source' => 'foo.bar @baz',
        'context' => NULL,
      ],
      [
        'source' => 'foo.bar :baz',
        'context' => NULL,
      ],
      [
        'source' => 'Hello sun.',
        'context' => NULL,
      ],
      [
        'source' => 'Hello sun.',
        'context' => 'Lolspeak',
      ],
      [
        'source' => 'Hello Earth.',
        'context' => NULL,
      ],
      [
        'source' => 'Hello moon.',
        'context' => NULL,
      ],
      [
        'source' => 'Hello moon.
    Hello sun.
    Hello world.',
        'context' => NULL,
      ],
      [
        'source' => [
          0 => 'Hello star.',
          1 => 'Hello @count stars.',
        ],
        'context' => NULL,
      ],
      [
        'source' => [
          0 => 'Hello star @complex_object.foo.',
          1 => 'Hello @count stars @complex_object.foo.',
        ],
        'context' => NULL,
      ],
      [
        'source' => [
          0 => 'Hello star @complex_object.foo.
    Hello moon.',
          1 => 'Hello @count stars @complex_object.foo.
    Hello @count moons.',
        ],
        'context' => NULL,
      ],
      [
        'source' => [
          0 => 'Hello star @complex_object.foo.
    Hello moon.',
          1 => 'Hello @count stars @complex_object.foo.
    Hello @count moons.',
        ],
        'context' => 'Lolspeak',
      ],
      [
        'source' => 'Hello moon @complex_object.foo.',
        'context' => NULL,
      ],
      [
        'source' => 'Escaped: @string',
        'context' => NULL,
      ],
      [
        'source' => 'Placeholder: @string',
        'context' => NULL,
      ],
      [
        'source' => 'This @token.name has a length of: @count. It contains: @token.numbers and @token.bad_text.',
        'context' => NULL,
      ],
      [
        'source' => 'I have context.',
        'context' => NULL,
      ],
      [
        'source' => 'I have context.',
        'context' => 'Lolspeak',
      ],
      [
        'source' => 'Hello new text.',
        'context' => NULL,
      ],
      [
        'source' => 'Hello new text.',
        'context' => 'Lolspeak',
      ],
      [
        'source' => "Number I never remember: ' . print(pi()) . '",
        'context' => NULL,
      ],
      [
        'source' => "Let's <strong>deploy</strong> to the <a href=\"https://www.drupal.org/project/potion\" target=\"_blank\">moon @string</a>.",
        'context' => NULL,
      ],
      [
        'source' => 'Hello Mars.',
        'context' => NULL,
      ],
      [
        'source' => 'Hello Jupiter.',
        'context' => NULL,
      ],
    ];

    foreach ($po_items as $po_item) {
      // Save source & translations as string for both singular & plural.
      $source      = is_array($po_item['source']) ? implode(PluralTranslatableMarkup::DELIMITER, $po_item['source']) : $po_item['source'];
      $translation = is_array($po_item['source']) ? implode(PluralTranslatableMarkup::DELIMITER, ['', '']) : '';

      $item = new PoItem();
      $item->setFromArray([
        'context'     => $po_item['context'],
        'source'      => $source,
        'translation' => $translation,
        'comment'     => NULL,
      ]);

      // Generate a uniq key by translations to avoid duplicates.
      $id = md5($source . $po_item['context']);
      $this->poItems[$id] = $item;
    }
  }

  /**
   * @covers \Drupal\potion\Extractor\TwigExtractor::extract
   */
  public function testExtractFull() {
    $extractor = new TwigExtractor($this->twig, TRUE);
    // Extract with recusrsivity to retrieive the complete set of translations.
    $actual = $extractor->extract($this->extractionPath, TRUE);

    $this->assertInstanceOf(MessageCatalogue::class, $actual);
    $this->assertContainsOnlyInstancesOf(PoItem::class, $actual->all());
    $this->assertEquals(25, $actual->count());

    // Asserts collection of objects are in the same order w/ same properties.
    $this->assertEquals($this->poItems, $actual->all());
  }

  /**
   * @covers \Drupal\potion\Extractor\TwigExtractor::extract
   */
  public function testExtractPartial() {
    $extractor = new TwigExtractor($this->twig, TRUE);
    // Extract whitout recusrsivity to retrieive a partial set of translations.
    $actual = $extractor->extract($this->extractionPath, FALSE);

    $this->assertInstanceOf(MessageCatalogue::class, $actual);
    $this->assertContainsOnlyInstancesOf(PoItem::class, $actual->all());
    $this->assertEquals(24, $actual->count());
  }

}
