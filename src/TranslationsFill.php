<?php

namespace Drupal\potion;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\locale\StringStorageInterface;
use Drupal\Component\Gettext\PoStreamReader;
use Drupal\locale\PoDatabaseReader;
use Drupal\Component\Gettext\PoStreamWriter;
use Drupal\potion\Exception\PotionException;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;

/**
 * Translations Fill.
 */
class TranslationsFill {

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
   * String translation storage object.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $localeStorage;

  /**
   * The catalogue of messages.
   *
   * @var \Drupal\potion\MessageCatalogue
   */
  protected $catalogue;

  /**
   * Class constructor.
   *
   * @param \Drupal\potion\Utility $utility
   *   Utility methods for Potion.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\locale\StringStorageInterface $local_storage
   *   String translation storage object.
   */
  public function __construct(Utility $utility, ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, StringStorageInterface $local_storage) {
    $this->utility       = $utility;
    $this->siteConfig    = $config_factory->get('system.site');
    $this->fileSystem    = $file_system;
    $this->localeStorage = $local_storage;

    $this->catalogue = new MessageCatalogue();

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
   * Re-fill an existing po file with translations from Drupal database.
   *
   * @param string $langcode
   *   Language code of the language being exported from the database.
   * @param string $source
   *   File to re-fill with fresh data.
   * @param bool $overwrite
   *   Should overwrite existing translations of $source with ditemata from db.
   *
   * @return SplFileInfo|null
   *   The temporary file containing all the original & filled translations.
   */
  public function fillFromDatabase($langcode, $source, $overwrite = FALSE) {

    // Check for existing & enabled langcode.
    if (!$this->utility->isLangcodeEnabled($langcode)) {
      throw PotionException::invalidLangcode($langcode);
    }

    // Check for existing path.
    if (!is_file($source)) {
      throw PotionException::notFound($source);
    }

    if (!is_readable($source)) {
      throw PotionException::isNotReadable($source);
    }

    // Check for existing source with valid content.
    if (!$this->utility->isValidPo($source)) {
      throw PotionException::invalidPo($source);
    }

    $reader = new PoStreamReader();
    $reader->setLangcode($langcode);
    $reader->setURI($source);

    try {
      $reader->open();
    }
    catch (\Exception $e) {
      throw new PotionException($e->getMessage(), $e->getCode(), $e);
    }

    while ($item = $reader->readItem()) {
      // TODO REFACTORING HERE.
      // Get the source from the file & format it.
      $source = $item->getSource();
      if ($item->isPlural()) {
        $source = implode(PluralTranslatableMarkup::DELIMITER, $source);
      }

      // Get the translation from the file & format it.
      $trans = $item->getTranslation();
      if ($item->isPlural()) {
        $trans = implode(PluralTranslatableMarkup::DELIMITER, $trans);
      }

      /** @var \Drupal\locale\SourceString $string */
      $local = $this->localeStorage->findString(['source' => $source, 'context' => $item->getContext()]);

      if ($local && empty($trans) || $local && $overwrite) {
        /** @var \Drupal\locale\TranslationString[] $trans */
        $trans = $this->localeStorage->getTranslations([
          'lid'        => $local->lid,
          'language'   => $langcode,
          'translated' => TRUE,
        ]);

        // Get the first translations.
        $trans = reset($trans);
        $trans = $trans ? $trans->getString() : NULL;
      }

      $this->catalogue->add($item->getSource(), $item->getContext(), $trans);
    }

    $reader = new PoDatabaseReader();
    $reader->setLangcode($langcode);
    $header = $reader->getHeader();
    $header->setProjectName($this->siteConfig->get('name'));
    $header->setLanguageName($this->utility->getLangName($langcode));

    $uri = $this->fileSystem->tempnam('temporary://', 'po_');
    $writer = new PoStreamWriter();
    $writer->setURI($uri);
    $writer->setHeader($header);
    $writer->open();

    // Write every Items one by one.
    foreach ($this->catalogue->all() as $item) {
      $this->report['strings'][] = $item->getSource();

      $trans = $item->getTranslation();
      if ($item->isPlural()) {
        $trans = implode('', $trans);
      }
      if (!empty($trans)) {
        $this->report['translated']++;
      }
      else {
        $this->report['untranslated']++;
      }

      $writer->writeItem($item);
    }
    $writer->close();

    return new \SplFileInfo($this->fileSystem->realpath($uri));
  }

}
