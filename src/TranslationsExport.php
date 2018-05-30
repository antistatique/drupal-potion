<?php

namespace Drupal\potion;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\locale\PoDatabaseReader;
use Drupal\potion\Exception\PotionException;
use Drupal\Component\Gettext\PoStreamWriter;

/**
 * Translations Exportations.
 */
class TranslationsExport {

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
   * Class constructor.
   *
   * @param \Drupal\potion\Utility $utility
   *   Utility methods for Potion.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(Utility $utility, ConfigFactoryInterface $config_factory, FileSystemInterface $file_system) {
    $this->utility    = $utility;
    $this->siteConfig = $config_factory->get('system.site');
    $this->fileSystem = $file_system;

    $this->setReport();
  }

  /**
   * Associative array summarizing export done.
   *
   * Keys for the array:
   *  - strings: source strings exported
   *  - untranslated: number of source strings whitout translations.
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
      'translated'   => 0,
      'untranslated' => 0,
      'strings'      => [],
    ];
    $this->report = $report;
  }

  /**
   * Translation(s) exportation from database to .po file.
   *
   * @param string $langcode
   *   Language code of the language being exported from the database.
   * @param array $options
   *   The Options.
   *   $options = [
   *     'non-customized' => (bool)
   *     'customized'     => (bool)
   *     'untranslated'   => (bool)
   *   ].
   *
   * @return SplFileInfo|null
   *   The temporary file containing all the exported translations.
   */
  public function exportFromDatabase($langcode, array $options = [
    'non-customized' => FALSE,
    'customized'     => FALSE,
    'untranslated'   => FALSE,
  ]) {
    // Check for existing & enabled langcode.
    if (!$this->utility->isLangcodeEnabled($langcode)) {
      throw PotionException::invalidLangcode($langcode);
    }

    $customized = FALSE;
    if (isset($options['customized']) && $options['customized']) {
      $customized = TRUE;
    }

    $non_customized = FALSE;
    if (isset($options['non-customized']) && $options['non-customized']) {
      $non_customized = TRUE;
    }

    $untranslated = FALSE;
    if (isset($options['untranslated']) && $options['untranslated']) {
      $untranslated = TRUE;
    }

    $reader = new PoDatabaseReader();
    $reader->setLangcode($langcode);
    $reader->setOptions([
      'not_customized' => $non_customized,
      'customized'     => $customized,
      'not_translated' => $untranslated,
    ]);

    $header = $reader->getHeader();
    $header->setProjectName($this->siteConfig->get('name'));
    $header->setLanguageName($this->utility->getLangName($langcode));

    $item = $reader->readItem();

    if (empty($item)) {
      return NULL;
    }

    $uri = $this->fileSystem->tempnam('temporary://', 'po_');

    $writer = new PoStreamWriter();
    $writer->setURI($uri);
    $writer->setHeader($header);

    $writer->open();
    $writer->writeItem($item);

    // Write every Items one by one.
    while ($item = $reader->readItem()) {
      $this->report['strings'][] = $item->getSource();

      // Report if has translations.
      if (empty($item->getTranslation())) {
        $this->report['untranslated']++;
      }
      else {
        $this->report['translated']++;
      }

      $writer->writeItem($item);
    }

    $writer->close();

    return new \SplFileInfo($this->fileSystem->realpath($uri));
  }

}
