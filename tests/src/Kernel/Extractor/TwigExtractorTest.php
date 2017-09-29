<?php

namespace Drupal\potion\tests\Kernel\Extractor;

use Drupal\KernelTests\KernelTestBase;
use Drupal\potion\Extractor\TwigExtractor;

/**
 * Tests Twig Extractor
 *
 * @group potion
 */
class TwigExtractorTest extends KernelTestBase  {

  public static $modules = [
    'locale',
    'language',
    'potion',
    'potion_test',
  ];

  /**
   * @var \Twig_Environment
   *  Twig environment
   */
  private $twig;

  protected function setUp() {
    parent::setUp();
    $this->twig = $this->container->get('twig');
  }

  public function testExtract() {
    $expected = [
      'foo.t',
      //'foo.trans',
      'foo.bar @baz',
      'foo trans block @complex_object.foo',
      'foo trans block @complex_object.foo and @count',
      'foo trans with placeholder @string', // @TODO: is this is expected behavior ? no more '!string'?
      "\nfoo trans with raw @string and space\n",
      'Hello star.',
      'Hello @count stars.',
      'foo.file2',
    ];

    $extractor = new TwigExtractor($this->twig);
    $path = __DIR__ . '/../../../modules/potion_test/templates';

    $actual = $extractor->extract($path);

    $this->assertSame($expected, $actual);
  }
}
