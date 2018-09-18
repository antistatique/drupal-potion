<?php

namespace Drupal\potion\tests\Kernel\Extractor;

use Drupal\KernelTests\KernelTestBase;
use Drupal\potion\Extractor\YamlExtractor;
use Drupal\Component\Gettext\PoItem;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\potion\MessageCatalogue;

/**
 * @coversDefaultClass \Drupal\potion\Extractor\YamlExtractor
 * @group potion
 * @group potion_kernel
 * @group potion_kernel_extractor_yaml
 */
class YamlExtractorTest extends KernelTestBase {

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
   * Extract Translations Annotation from PHP Class files.
   *
   * @var \Drupal\potion\Extractor\YamlExtractor
   */
  protected $yamlExtractor;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var string $extractionPath */
    $this->extractionPath = drupal_get_path('module', 'potion_test');

    /** @var \Drupal\potion\Extractor\YamlExtractor $yamlExtractor */
    $this->yamlExtractor = $this->container->get('potion.extractor.yaml');

    $po_items = [
      [
        'source' => 'Potion overview',
        'context' => NULL,
      ],
      [
        'source' => 'Lorem Ipsum',
        'context' => 'Lolspeak',
      ],
      [
        'source' => 'Ipsum',
        'context' => NULL,
      ],
      [
        'source' => 'Lorem',
        'context' => NULL,
      ],
      [
        'source' => 'Ipsum',
        'context' => 'Lolspeak',
      ],
      [
        'source' => 'Potion test',
        'context' => NULL,
      ],
      [
        'source' => 'Support module for Potion testing.',
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
   * @covers \Drupal\potion\Extractor\YamlExtractor::extract
   */
  public function testExtractFull() {
    // Extract with recusrsivity to retrieive the complete set of translations.
    $actual = $this->yamlExtractor->extract($this->extractionPath, TRUE);

    $this->assertInstanceOf(MessageCatalogue::class, $actual);
    $this->assertContainsOnlyInstancesOf(PoItem::class, $actual->all());
    $this->assertEquals(7, $actual->count());

    // Asserts collection of objects are in the same order w/ same properties.
    $this->assertEquals($this->poItems, $actual->all());
  }

  /**
   * @covers \Drupal\potion\Extractor\YamlExtractor::extract
   */
  public function testExtractPartial() {
    // Extract whitout recusrsivity to retrieive a partial set of translations.
    $actual = $this->yamlExtractor->extract($this->extractionPath, FALSE);

    $this->assertInstanceOf(MessageCatalogue::class, $actual);
    $this->assertContainsOnlyInstancesOf(PoItem::class, $actual->all());
    $this->assertEquals(7, $actual->count());
  }

}
