<?php

namespace Drupal\potion;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\potion\Extractor\TwigExtractor;
use Drupal\potion\Extractor\PhpExtractor;
use Drupal\locale\PoDatabaseReader;
use Drupal\potion\Exception\PotionException;
use Drupal\Component\Gettext\PoStreamWriter;

/**
 * Translations Extractor.
 */
class TranslationsExtractor {

  /**
   * The Utility service of Potion.
   *
   * @var \Drupal\potion\Utility
   */
  protected $utility;

  /**
   * The site settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $siteConfig;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Extract Translations from Twig templates.
   *
   * @var \Drupal\potion\Extractor\TwigExtractor
   */
  protected $twigExtractor;

  /**
   * Extract Translations from PHP files.
   *
   * @var \Drupal\potion\Extractor\PhpExtractor
   */
  protected $phpExtractor;

  /**
   * Class constructor.
   *
   * @param \Drupal\potion\Utility $utility
   *   Utility methods for Potion.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\potion\Extractor\TwigExtractor $twig_extractor
   *   Extract Translations from Twig templates.
   * @param \Drupal\potion\Extractor\PhpExtractor $php_extractor
   *   Extract Translations from PHP files.
   */
  public function __construct(Utility $utility, ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, TwigExtractor $twig_extractor, PhpExtractor $php_extractor) {
    $this->utility       = $utility;
    $this->siteConfig    = $config_factory->get('system.site');
    $this->fileSystem    = $file_system;
    $this->twigExtractor = $twig_extractor;
    $this->phpExtractor  = $php_extractor;

    $this->setReport();
  }

  /**
   * Associative array summarizing extractions done.
   *
   * Keys for the array:
   *  - strings: source strings extracted.
   *  - twig: number of source strings founded on twig file.
   *  - php: number of source strings founded on php files.
   *  - yml: number of source strings founded on yml files.
   *
   * @var array
   */
  private $report;

  /**
   * Get the report of the write operations.
   */
  public function getReport() {
    return $this->report;
  }

  /**
   * Set the report array of write operations.
   *
   * @param array $report
   *   Associative array with result information.
   */
  public function setReport(array $report = []) {
    $report += [
      'twig'    => 0,
      'php'     => 0,
      'yml'     => 0,
      'strings' => [],
    ];
    $this->report = $report;
  }

  /**
   * Translation(s) extractions from file system to .po file.
   *
   * @param string $langcode
   *   Language code for extractions.
   * @param string $source
   *   Base path directory to lookup for files.
   * @param string $destination
   *   The destination .po file.
   * @param bool $recursive
   *   Should recursively lookup for files.
   * @param bool $merge
   *   Should merge the .po w/ an existing .po file in the destination.
   * @param array $exclusion
   *   The exclusions options.
   *   $options = [
   *     'exclude-yaml' => (bool)
   *     'exclude-twig' => (bool)
   *     'exclude-php'  => (bool)
   *   ].
   *
   * @return array
   *   Report array..
   */
  public function extract($langcode, $source, $destination, $recursive = FALSE, $merge = FALSE, array $exclusion = [
    'exclude-yaml' => FALSE,
    'exclude-twig' => FALSE,
    'exclude-php'  => FALSE,
  ]) {

    // Check for existing & enabled langcode.
    if (!$this->utility->isLangcodeEnabled($langcode)) {
      throw PotionException::invalidLangcode($langcode);
    }

    // Check for existing path.
    if (!is_dir($source)) {
      throw PotionException::notFound($source);
    }

    if (!is_readable($source)) {
      throw PotionException::isNotReadable($source);
    }

    // Check for existing destination file.
    if (!is_dir($destination)) {
      throw PotionException::notFound($destination);
    }

    // Check for writable destination.
    if (!is_writable($destination)) {
      throw PotionException::isNotWritable($destination);
    }

    $reader = new PoDatabaseReader();
    $reader->setLangcode($langcode);
    $header = $reader->getHeader();
    $header->setProjectName($this->siteConfig->get('name'));
    $header->setLanguageName($this->utility->getLangName($langcode));

    $twig_translations = [];
    if (!$exclusion['exclude-twig']) {
      $twig_translations = $this->twigExtractor->extract($source, $recursive);
      $this->report['twig'] = count($twig_translations);
    }

    $php_translations = [];
    if (!$exclusion['exclude-php']) {
      $php_translations = $this->phpExtractor->extract($source, $recursive);
      $this->report['php'] = count($php_translations);
    }

    $yaml_translations = [];
    if (!$exclusion['exclude-yaml']) {
      $this->report['yml'] = count($yaml_translations);
    }

    // Concat every extractors into a single array for write processing.
    $items = array_merge($twig_translations, $php_translations, $yaml_translations);

    $uri = $this->fileSystem->tempnam('temporary://', 'po_');

    $writer = new PoStreamWriter();
    $writer->setURI($uri);
    $writer->setHeader($header);
    $writer->open();

    // Write every Items one by one.
    foreach ($items as $item) {
      $this->report['strings'][] = $item->getSource();
      $writer->writeItem($item);
    }

    $writer->close();

    // Get the final destination path.
    $fullpath = $this->utility->sanitizePath($this->fileSystem->realpath($destination)) . $langcode . '.po';

    // Perform the move operation.
    rename($this->fileSystem->realpath($uri), $fullpath);

    return $this->report;
  }

}
