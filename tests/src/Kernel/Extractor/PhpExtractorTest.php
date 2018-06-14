<?php

namespace Drupal\potion\tests\Kernel\Extractor;

use Drupal\KernelTests\KernelTestBase;
use Drupal\potion\Extractor\PhpExtractor;
use Drupal\Component\Gettext\PoItem;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\potion\MessageCatalogue;

/**
 * @coversDefaultClass \Drupal\potion\Extractor\PhpExtractor
 * @group potion
 * @group potion_kernel
 * @group potion_kernel_extractor_php
 */
class PhpExtractorTest extends KernelTestBase {

  public static $modules = [
    'locale',
    'language',
    'potion',
    'potion_test',
  ];

  /**
   * The directory of tests for php extractions.
   *
   * @var array
   */
  protected $extractionPath;

  /**
   * Collection of expected extracted translantions strings.
   *
   * Collection of poItems that should be generated when using the extractor
   * twig-only on the potion_tests dir.
   *
   * @var \Drupal\Component\Gettext\PoItem[]
   */
  private $poItems;

  /**
   * Extract Translations from PHP files.
   *
   * @var \Drupal\potion\Extractor\PhpExtractor
   */
  protected $phpExtractor;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var string $extractionPath */
    $this->extractionPath = drupal_get_path('module', 'potion_test');

    /** @var \Drupal\potion\Extractor\PhpExtractor $phpExtractor */
    $this->phpExtractor = $this->container->get('potion.extractor.php');

    $po_items = [
      [
        'source' => 'php.context',
        'context' => 'Lolspeak',
      ],
      [
        'source' => 'php.baz',
        'context' => NULL,
      ],
      [
        'source' => 'php.foo',
        'context' => NULL,
      ],
      [
        'source' => 'php.foo.bar',
        'context' => NULL,
      ],
      [
        'source' => 'php.foo.bar @var',
        'context' => NULL,
      ],
      [
        'source' => 'php.foo.module',
        'context' => NULL,
      ],
      [
        'source' => 'Hello sunshine',
        'context' => NULL,
      ],
      [
        'source' => 'Hello sunshine @bar',
        'context' => NULL,
      ],
      [
        'source' => 'Hello sunshine :bar',
        'context' => NULL,
      ],
      [
        'source' => 'Hello sunshine @bar :baz',
        'context' => NULL,
      ],
      [
        'source' => 'php.context.ru',
        'context' => 'Lolspeak',
      ],
      [
        'source' => 'Hello dawn',
        'context' => NULL,
      ],
      [
        'source' => 'Hello dawny',
        'context' => NULL,
      ],
      [
        'source' => 'Hello dawn @bar',
        'context' => NULL,
      ],
      [
        'source' => 'Hello dawny @bar',
        'context' => NULL,
      ],
      [
        'source' => 'Hello dawn @bar',
        'context' => 'Lolspeak',
      ],
      [
        'source' => 'Hello dawny @bar',
        'context' => 'Lolspeak',
      ],
      [
        'source' => [
          0 => 'singular @count',
          1 => 'plural @count',
        ],
        'context' => NULL,
      ],
      [
        'source' => [
          0 => 'singular @count @foo',
          1 => 'plural @count @foo',
        ],
        'context' => NULL,
      ],
      [
        'source' => [
          0 => 'singular @count',
          1 => 'plural @count',
        ],
        'context' => 'Lolspeak',
      ],
      [
        'source' => [
          0 => '1 byte',
          1 => '@count bytes',
        ],
        'context' => NULL,
      ],
      [
        'source' => [
          0 => '1 byte',
          1 => '@count bytes',
        ],
        'context' => 'Lolspeak',
      ],
      [
        'source' => 'Hello moonlight',
        'context' => NULL,
      ],
      [
        'source' => 'Hello moonlight @foobar',
        'context' => NULL,
      ],
      [
        'source' => 'Hello moonlight @foobar',
        'context' => 'Lolspeak',
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
   * @covers \Drupal\potion\Extractor\PhpExtractor::extract
   */
  public function testExtractFull() {
    // Extract with recusrsivity to retrieive the complete set of translations.
    $actual = $this->phpExtractor->extract($this->extractionPath, TRUE);

    $this->assertInstanceOf(MessageCatalogue::class, $actual);
    $this->assertContainsOnlyInstancesOf(PoItem::class, $actual->all());
    $this->assertEquals(25, $actual->count());

    // Asserts collection of objects are in the same order w/ same properties.
    $this->assertEquals($this->poItems, $actual->all());
  }

  /**
   * @covers \Drupal\potion\Extractor\PhpExtractor::extract
   */
  public function testExtractPartialRecusrive() {
    // Extract whitout recusrsivity to retrieive a partial set of translations.
    $actual = $this->phpExtractor->extract($this->extractionPath, FALSE);
    $this->assertInstanceOf(MessageCatalogue::class, $actual);
    $this->assertContainsOnlyInstancesOf(PoItem::class, $actual->all());
    $this->assertEquals(2, $actual->count());
  }

  /**
   * @covers \Drupal\potion\Extractor\PhpExtractor::extract
   */
  public function testExtractPartial() {
    // Extract whitout recusrsivity to retrieive a partial set of translations.
    $actual = $this->phpExtractor->extract($this->extractionPath . DIRECTORY_SEPARATOR . 'inc', FALSE);
    $this->assertInstanceOf(MessageCatalogue::class, $actual);
    $this->assertContainsOnlyInstancesOf(PoItem::class, $actual->all());
    $this->assertEquals(4, $actual->count());
  }

}
