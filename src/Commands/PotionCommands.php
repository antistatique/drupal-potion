<?php

namespace Drupal\potion\Commands;

use Drush\Commands\DrushCommands;
use Drupal\potion\Utility;
use Drupal\potion\TranslationsImport;
use Drupal\potion\TranslationsExport;
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
  protected $translationsImport;

  /**
   * The Translation exporter.
   *
   * @var \Drupal\potion\TranslationsExport
   */
  protected $translationsExport;

  /**
   * Class constructor.
   *
   * @param \Drupal\potion\Utility $utility
   *   Utility methods for Potion.
   * @param \Drupal\potion\TranslationsImport $translations_import
   *   The Translation importer service.
   * @param \Drupal\potion\TranslationsExport $translations_export
   *   The Translation exporter service.
   */
  public function __construct(Utility $utility, TranslationsImport $translations_import, TranslationsExport $translations_export) {
    $this->utility = $utility;
    $this->translationsImport = $translations_import;
    $this->translationsExport = $translations_export;
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

    $report = $this->translationsImport->importFromFile($langcode, $source, $options);

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
   *   The destination .po file.
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
   * @usage drush potion:export langcode path/to/destination.po
   *   Export translations in the langcode to the given destination .po file.
   * @usage drush potion:export fr path/to/export/
   *   Export French translations to the path/to/export/fr.po file.
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
    $msg = dt('You are about to overwrite the @file. Do you want to continue?', ['@file' => $fullpath]);
    if (is_file($fullpath) && !$this->io()->confirm($msg)) {
      throw new UserAbortException();
    }

    $report = $this->translationsExport->exportFromDatabase($langcode, $destination, $options);
    $rows = [];
    $rows[] = [
      'total'        => count($report['strings']),
      'translated'   => $report['translated'],
      'untranslated' => $report['untranslated'],
    ];
    return new RowsOfFields($rows);
  }

}
