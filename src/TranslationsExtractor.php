<?php

namespace Drupal\potion;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\potion\Extractor\TwigExtractor;
use Drupal\potion\Extractor\PhpExtractor;
use Drupal\potion\Extractor\AnnotationExtractor;
use Drupal\potion\Extractor\YamlExtractor;
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
   * Extract Translations Annotation from PHP Class files.
   *
   * @var \Drupal\potion\Extractor\AnnotationExtractor
   */
  protected $annotationExtractor;

  /**
   * Extract Translations from YAML files.
   *
   * @var \Drupal\potion\Extractor\YamlExtractor
   */
  protected $yamlExtractor;

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
   * @param \Drupal\potion\Extractor\AnnotationExtractor $annotation_extractor
   *   Extract Translations Annotation from PHP Class files.
   * @param \Drupal\potion\Extractor\YamlExtractor $yaml_extractor
   *   Extract Translations Annotation from PHP Class files.
   */
  public function __construct(Utility $utility, ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, TwigExtractor $twig_extractor, PhpExtractor $php_extractor, AnnotationExtractor $annotation_extractor, YamlExtractor $yaml_extractor) {
    $this->utility    = $utility;
    $this->siteConfig = $config_factory->get('system.site');
    $this->fileSystem = $file_system;

    // Extractors.
    $this->twigExtractor       = $twig_extractor;
    $this->phpExtractor        = $php_extractor;
    $this->annotationExtractor = $annotation_extractor;
    $this->yamlExtractor       = $yaml_extractor;

    $this->setReport();
  }

  /**
   * Associative array summarizing extractions done.
   *
   * Keys for the array:
   *  - strings: source strings extracted.
   *  - twig: number of source strings founded on twig file.
   *  - php: number of source strings founded on php files.
   *  - yaml: number of source strings founded on yaml files.
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
      'yaml'    => 0,
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
   * @param bool $recursive
   *   Should recursively lookup for files.
   * @param array $exclusion
   *   The exclusions options.
   *   $options = [
   *     'exclude-yaml' => (bool)
   *     'exclude-twig' => (bool)
   *     'exclude-php'  => (bool)
   *   ].
   *
   * @return SplFileInfo|null
   *   The temporary file containing all the extracted translations.
   */
  public function extract($langcode, $source, $recursive = FALSE, array $exclusion = [
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

    // Will contains every extracted translations messages.
    $catalogue = new MessageCatalogue();

    if (!$exclusion['exclude-twig']) {
      $twig_catalogue = $this->twigExtractor->extract($source, $recursive);
      $this->report['twig'] = $twig_catalogue->count();
      $catalogue->merge($twig_catalogue);
    }

    if (!$exclusion['exclude-php']) {
      $php_catalogue = $this->phpExtractor->extract($source, $recursive);
      $annotation_catalogue = $this->annotationExtractor->extract($source, $recursive);
      $this->report['php'] = $php_catalogue->count() + $annotation_catalogue->count();
      $catalogue->merge($php_catalogue);
      $catalogue->merge($annotation_catalogue);
    }

    if (!$exclusion['exclude-yaml']) {
      $yaml_catalogue = $this->yamlExtractor->extract($source, $recursive);
      $this->report['yaml'] = $yaml_catalogue->count();
      $catalogue->merge($yaml_catalogue);
    }

    $messages = $catalogue->all();
    if (empty($messages)) {
      return NULL;
    }

    // Create a temporay file to write into.
    $uri = $this->fileSystem->tempnam('temporary://', 'po_');

    // Prepare a reader to generate the futur .po header.
    $reader = new PoDatabaseReader();
    $reader->setLangcode($langcode);
    $header = $reader->getHeader();
    $header->setProjectName($this->siteConfig->get('name'));
    $header->setLanguageName($this->utility->getLangName($langcode));

    $writer = new PoStreamWriter();
    $writer->setURI($uri);
    $writer->setHeader($header);
    $writer->open();

    // Write every message one by one.
    foreach ($messages as $message) {
      $this->report['strings'][] = $message->getSource();
      $writer->writeItem($message);
    }

    $writer->close();

    return new \SplFileInfo($this->fileSystem->realpath($uri));
  }

}
