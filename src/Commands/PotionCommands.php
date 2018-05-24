<?php

namespace Drupal\potion\Commands;

use Drush\Commands\DrushCommands;
use Drupal\potion\Utility;
use Drupal\potion\TranslationsImport;
use Drupal\potion\TranslationsExport;
use Drupal\potion\TranslationsExtractor;
use Drupal\potion\Exception\ConsoleException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drush\Exceptions\UserAbortException;

/**
 * Defines Drush commands for Potion.
 */
class PotionCommands extends DrushCommands {
  use StringTranslationTrait;

  /**
   * The Utility service of Potion.
   *
   * @var \Drupal\potion\Utility
   */
  protected $utility;

  /**
   * The Translation importer.
   *
   * @var \Drupal\potion\TranslationsImport
   */
  protected $transImport;

  /**
   * The Translation exporter.
   *
   * @var \Drupal\potion\TranslationsExport
   */
  protected $transExport;

  /**
   * The Translation extractor service.
   *
   * @var \Drupal\potion\TranslationsExtractor
   */
  protected $transExtractor;

  /**
   * Class constructor.
   *
   * @param \Drupal\potion\Utility $utility
   *   Utility methods for Potion.
   * @param \Drupal\potion\TranslationsImport $translations_import
   *   The Translation importer service.
   * @param \Drupal\potion\TranslationsExport $translations_export
   *   The Translation exporter service.
   * @param \Drupal\potion\TranslationsExtractor $translations_extractor
   *   The Translation extractor service.
   */
  public function __construct(Utility $utility, TranslationsImport $translations_import, TranslationsExport $translations_export, TranslationsExtractor $translations_extractor) {
    $this->utility        = $utility;
    $this->transImport    = $translations_import;
    $this->transExport    = $translations_export;
    $this->transExtractor = $translations_extractor;
  }

  /**
   * Translation(s) importation from .po file in the database.
   *
   * Expose the Core feature of translation importation.
   * See the online documentation for the Language
   * module https://www.drupal.org/documentation/modules/language.
   *
   * @param string $langcode
   *   The langcode to import. Eg. 'en' or 'fr'.
   * @param string $source
   *   The source .po file.
   * @param array $options
   *   (optional) An array of options.
   *
   * @command potion:import
   *
   * @option mode
   *   Define the importation mode from 'customized' or 'non-customized'.
   *   Use 'non-customized' when translations are imported from .po files
   *   downloaded from localize.drupal.org for example.
   *   Use 'customized' when translations are edited from their imported
   *   originals on the user interface or are imported as customized.
   * @option overwrite
   *   Overwrite existing translations with values from the source file.
   *   [default: "false"].
   *
   * @usage drush potion:import langcode path/to/source.po
   *   Import translations in the langcode from the given source .po file.
   * @usage drush potion:import fr path/to/fr.po
   *   Import French translations from the fr.po file.
   *
   * @validate-module-enabled locale, language, file
   *
   * @aliases po:import
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Formatted output summary.
   *
   * @throws \Drupal\potion\Exception\ConsoleException
   *   If no langcode isn't a valid enabled language.
   * @throws \Drupal\potion\Exception\ConsoleException
   *   If the given source isn't a valid or malformed .po file.
   */
  public function import($langcode, $source, array $options = [
    'format'    => 'table',
    'mode'      => 'non-customized',
    'overwrite' => FALSE,
  ]) {
    // Check for existing & enabled langcode.
    if (!$this->utility->isLangcodeEnabled($langcode)) {
      throw ConsoleException::invalidLangcode($langcode);
    }

    // Check for existing source with valid content.
    if (!$this->utility->isValidPo($source)) {
      throw ConsoleException::invalidPo($source);
    }

    if (!in_array($options['mode'], ['customized', 'non-customized'])) {
      throw ConsoleException::invalidMode($options['mode']);
    }

    // Use Drupal mode constant for better abstraction.
    $options['customized'] = $options['mode'] == 'customized' ? LOCALE_CUSTOMIZED : LOCALE_NOT_CUSTOMIZED;
    unset($options['mode']);

    $report = $this->transImport->importFromFile($langcode, $source, $options);

    $rows = [];
    $rows[] = [
      'total'     => count($report['strings']),
      'additions' => $report['additions'],
      'updates'   => $report['updates'],
      'deletes'   => $report['deletes'],
      'skips'     => $report['skips'],
    ];
    return new RowsOfFields($rows);
  }

  /**
   * Translation(s) exportation from database to .po file.
   *
   * Expose the Core feature of translation exportation.
   * See the online documentation for the Language
   * module https://www.drupal.org/documentation/modules/language.
   *
   * @param string $langcode
   *   The langcode to import. Eg. 'en' or 'fr'.
   * @param string $destination
   *   The destination path.
   * @param array $options
   *   (optional) An array of options.
   *
   * @command potion:export
   *
   * @option non-customized
   *   Include non-customized translations
   *   [default: "false"].
   *
   * @option customized
   *   Include customized translations
   *   [default: "false"].
   *
   * @option untranslated
   *   Include untranslated text
   *   [default: "false"].
   *
   * @usage drush potion:export langcode path/to/destination/
   *   Export translations in the langcode to the given destination .po file.
   * @usage drush potion:export fr path/to/destination/
   *   Export French translations to the path/to/destination/fr.po file.
   *
   * @validate-module-enabled locale, language, file
   *
   * @aliases po:export
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Formatted output summary.
   *
   * @throws \Drupal\potion\Exception\ConsoleException
   *   If no langcode isn't a valid enabled language.
   * @throws \Drupal\potion\Exception\ConsoleException
   *   If the given source already exists.
   */
  public function export($langcode, $destination, array $options = [
    'format'         => 'table',
    'non-customized' => FALSE,
    'customized'     => FALSE,
    'untranslated'   => FALSE,
  ]) {
    // Check for existing & enabled langcode.
    if (!$this->utility->isLangcodeEnabled($langcode)) {
      throw ConsoleException::invalidLangcode($langcode);
    }

    // Check for existing destination file.
    if (!is_dir($destination)) {
      throw ConsoleException::notFound($destination);
    }

    // Check for writable destination.
    if (!is_writable($destination)) {
      throw ConsoleException::isNotWritable($destination);
    }

    $fullpath = $this->utility->sanitizePath($destination) . $langcode . '.po';

    // If file already exists in dest, ask questions before overwrite.
    $msg = $this->t('You are about to overwrite the @file. Do you want to continue?', ['@file' => $fullpath]);
    if (is_file($fullpath) && !$this->io()->confirm($msg)) {
      throw new UserAbortException();
    }

    $report = $this->transExport->exportFromDatabase($langcode, $destination, $options);
    $rows = [];
    $rows[] = [
      'total'        => count($report['strings']),
      'translated'   => $report['translated'],
      'untranslated' => $report['untranslated'],
    ];
    return new RowsOfFields($rows);
  }

  /**
   * Generate Translations from versatils sources.
   *
   * Parse all the files from the source & generate a fresh  langcode.po file.
   * If a .po file already exists in the destination dir,
   * merge them & remove duplicates.
   *
   * @param string $langcode
   *   The langcode to import. Eg. 'en' or 'fr'.
   * @param string $source
   *   The source folder to scan for translations.
   * @param string $destination
   *   The destination path.
   * @param array $options
   *   (optional) An array of options.
   *
   * @command potion:generate
   *
   * @option exclude-yaml
   *   Exclude YAML files (.yaml) to be scanned for translations.
   *   [default: "false"].
   *
   * @option exclude-twig
   *   Exclude TWIG files (.twig) to be scanned for translations.
   *   [default: "false"].
   *
   * @option exclude-php
   *   Exclude PHP files (.php, .module) to be scanned for translations.
   *   [default: "false"].
   *
   * @option recursive
   *   Enable scan recursion on the source folder.
   *   [default: "false"].
   *
   * @usage drush potion-generate langcode path/to/scan/ path/to/export/
   *   Generate translations in the langcode from a given folder to the given
   *   destination.
   * @usage drush potion-generate fr path/to/scan/ path/to/export/
   *   Generate French translations from files of path/to/scan/ to the given
   *   path/to/export/fr.po file.
   * @usage drush potion-generate fr path/to/scan/ path/to/export/ --recursive
   *   Generate French translations from all files (recusively) of path/to/scan/
   *   to the given path/to/export/fr.po file.
   * @usage drush potion-generate fr path/to/scan/ path/to/export/ --exclude-yaml
   *   Generate French translations from files of path/to/scan/, excepted Yaml
   *   ones, to the given path/to/export/fr.po file.
   *
   * @validate-module-enabled locale, language, file
   *
   * @aliases po:gen
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Formatted output summary.
   *
   * @throws \Drupal\potion\Exception\ConsoleException
   *   If no langcode isn't a valid enabled language.
   * @throws \Drupal\potion\Exception\ConsoleException
   *   If the given source does not exists.
   * @throws \Drupal\potion\Exception\ConsoleException
   *   If the given source is not readable.
   * @throws \Drupal\potion\Exception\ConsoleException
   *   If the given destination does not exists.
   * @throws \Drupal\potion\Exception\ConsoleException
   *   If the given destination is not writable.
   */
  public function translationExtract($langcode, $source, $destination, array $options = [
    'format'       => 'table',
    'exclude-yaml' => FALSE,
    'exclude-twig' => FALSE,
    'exclude-php'  => FALSE,
    'recursive'    => FALSE,
  ]) {
    // Check for existing & enabled langcode.
    if (!$this->utility->isLangcodeEnabled($langcode)) {
      throw ConsoleException::invalidLangcode($langcode);
    }

    // Check for existing path.
    if (!is_dir($source)) {
      throw ConsoleException::notFound($source);
    }

    if (!is_readable($source)) {
      throw ConsoleException::isNotReadable($source);
    }

    // Check for existing destination file.
    if (!is_dir($destination)) {
      throw ConsoleException::notFound($destination);
    }

    // Check for writable destination.
    if (!is_writable($destination)) {
      throw ConsoleException::isNotWritable($destination);
    }

    // @TODO - Ask questions to merge files togethers in the destination.
    // If file already exists in dest, ask questions before merging.
    // $msg = $this->t('You are about to overwrite the @file. Do you want to continue?', ['@file' => $fullpath]);
    // if (is_file($fullpath) && !$this->io()->confirm($msg)) {
    //   throw new UserAbortException();
    // }
    $report = $this->transExtractor->extract($langcode, $source, $destination, $options['recursive'], [
      'exclude-yaml' => $options['exclude-yaml'],
      'exclude-twig' => $options['exclude-twig'],
      'exclude-php'  => $options['exclude-php'],
    ]);
    $rows = [];
    $rows[] = [
      'total' => count($report['strings']),
      'twig'  => $report['twig'],
      'php'   => $report['php'],
      'yml'   => $report['yml'],
    ];
    return new RowsOfFields($rows);
  }

}
