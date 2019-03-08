<?php

namespace Drupal\potion;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\locale\Gettext;
use Drupal\potion\Exception\PotionException;

/**
 * Translations Importations.
 */
class TranslationsImport {

  /**
   * The Utility service of Potion.
   *
   * @var \Drupal\potion\Utility
   */
  protected $utility;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(Utility $utility, ModuleHandlerInterface $module_handler, FileSystemInterface $file_system) {
    $this->utility = $utility;
    $this->moduleHandler = $module_handler;
    $this->fileSystem = $file_system;
  }

  /**
   * Translation(s) importation from .po file in the database.
   *
   * @param string $langcode
   *   Language code of the language being written to the database.
   * @param string $source
   *   The .po file's path.
   * @param array $options
   *   The Options.
   *   $options = [
   *     'customized' => (integer)
   *     'overwrite' => (bool)
   *   ].
   *
   * @return array
   *   Report array as defined in Drupal\locale\PoDatabaseWriter.
   */
  public function importFromFile($langcode, $source, array $options = [
    'customized' => LOCALE_NOT_CUSTOMIZED,
    'overwrite' => FALSE,
  ]) {
    // Check for existing & enabled langcode.
    if (!$this->utility->isLangcodeEnabled($langcode)) {
      throw PotionException::invalidLangcode($langcode);
    }

    // Check for existing source with valid .po content.
    if (!$this->utility->isValidPo($source)) {
      throw PotionException::invalidPo($source);
    }

    // Load Drupal 8 Core local global functions.
    $this->moduleHandler->loadInclude('locale', 'translation.inc');
    $this->moduleHandler->loadInclude('locale', 'bulk.inc');

    $customized = LOCALE_NOT_CUSTOMIZED;
    if (isset($options['customized']) && $options['customized']) {
      $customized = LOCALE_CUSTOMIZED;
    }

    $overwrite = FALSE;
    if (isset($options['overwrite']) && $options['overwrite']) {
      $overwrite = (bool) $options['overwrite'];
    }

    $options = array_merge(_locale_translation_default_update_options(), [
      'customized'        => $customized,
      'overwrite_options' => [
        'not_customized' => !$customized && $overwrite ? TRUE : FALSE,
        'customized'     => $customized && $overwrite ? TRUE : FALSE,
      ],
    ]);

    // Create a valid file class for Gettext::fileToDatabase.
    $file            = new \stdClass();
    $file->filename  = $this->fileSystem->basename($source);
    $file->uri       = $source;
    $file->langcode  = $langcode;
    $file->timestamp = filemtime($source);

    return Gettext::fileToDatabase($file, $options);
  }

}
