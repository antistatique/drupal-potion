<?php

namespace Drupal\potion\Commands;

use Drush\Commands\DrushCommands;
use Drupal\potion\Utility;
use Drupal\potion\TranslationsImport;
use Drupal\potion\Exception\ConsoleException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Defines Drush commands for Potion.
 */
class PotionCommands extends DrushCommands {
  use StringTranslationTrait;

  /**
   * The Translation importer.
   *
   * @var \Drupal\potion\TranslationsImport
   */
  protected $translationsImport;

  /**
   * Class constructor.
   *
   * @param \Drupal\potion\Utility $utility
   *   Utility methods for Potion.
   * @param \Drupal\potion\TranslationsImport $translations_import
   *   The Translation importer service.
   */
  public function __construct(Utility $utility, TranslationsImport $translations_import) {
    $this->utility = $utility;
    $this->translationsImport = $translations_import;
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
   * Expose the [Core feature of translation exportation]
   * (/admin/config/regional/translate/export).
   *
   * @command potion:export
   *
   * @usage drush potion:export langcode dest
   *   Export translations in the langcode into the given destination .po file.
   *
   * @validate-module-enabled locale, language, file
   *
   * @aliases po:export
   *
   * @throws \Drupal\potion\Exception\ConsoleException
   *   If no langcode isn't a valid enabled language.
   * @throws \Drupal\potion\Exception\ConsoleException
   *   If dest file exists and is not empty and is a malformed .po file.
   */
  public function export($langcode, $destination, $options = []) {
    echo 'Export';
    die();
  }

}
