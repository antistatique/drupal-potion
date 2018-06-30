<?php

namespace Drupal\potion;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\potion\Exception\PotionException;

/**
 * Contains utility methods for the Potion module.
 */
class Utility {
  /**
   * The language Manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManager
   */
  protected $languageManager;

  /**
   * The Gettext wrapper.
   *
   * @var \Drupal\potion\GettextWrapper
   */
  protected $gettextWrapper;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\potion\GettextWrapper $gettext_wrapper
   *   The Gettext wrapper.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(LanguageManagerInterface $language_manager, GettextWrapper $gettext_wrapper, FileSystemInterface $file_system) {
    $this->languageManager = $language_manager;
    $this->gettextWrapper  = $gettext_wrapper;
    $this->fileSystem      = $file_system;
  }

  /**
   * From a given langcode, retrieve the langname.
   *
   * @param string $langcode
   *   The langcode.
   *
   * @return string|null
   *   The common langname of this langcode. Otherwise NULL
   */
  public function getLangName($langcode) {
    $languages = $this->languageManager->getLanguages();
    return isset($languages[$langcode]) ? $languages[$langcode]->getName() : NULL;
  }

  /**
   * Check if the given langcode is installed & enabled.
   *
   * @param string $langcode
   *   The langcode to test.
   *
   * @return bool
   *   TRUE if the given langcode exists, FALSE otherwise.
   */
  public function isLangcodeEnabled($langcode) {
    $languages = $this->languageManager->getLanguages();
    return isset($languages[$langcode]);
  }

  /**
   * Check if the given file path is a valid .po file.
   *
   * @param string $src
   *   The file to validate.
   *
   * @return bool
   *   TRUE if the given file is valid, FALSE otherwise.
   *
   * @throws \Drupal\potion\Exception\GettextException
   */
  public function isValidPo($src) {
    if (!is_file($src)) {
      return FALSE;
    }

    return $this->gettextWrapper->msgfmt($src);
  }

  /**
   * Determines whether this PHP process is running on the command line.
   *
   * @return bool
   *   TRUE if this PHP process is running via CLI, FALSE otherwise.
   */
  public static function isRunningInCli() {
    return php_sapi_name() === 'cli';
  }

  /**
   * Sanitize the given path to append a trailing director separator.
   *
   * @param mixed $path
   *   A given path string or a stream.
   *
   * @return string
   *   The path with a trailing director separator when needed.
   */
  public function sanitizePath($path) {
    // Only trim on non-empty path.
    if (empty($path)) {
      return $path;
    }

    // Only trim if we're not dealing with a stream.
    if (!file_stream_wrapper_valid_scheme($this->fileSystem->uriScheme($path)) || substr($path, -strlen('://')) !== '://') {
      $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
    return $path;
  }

  /**
   * Merge all $files in the $original PO file.
   *
   * Before merging, generate an incremental backup of $original.
   *
   * @param string $original
   *   The original PO file.
   * @param string[] $files
   *   The po files to merges. Those files should not contain a PO Header
   *   to avoid merge conflict.
   *
   * @return bool
   *   TRUE if the merge works, FALSE otherwise.
   *
   * @throws \Drupal\potion\Exception\GettextException
   * @throws \Drupal\potion\Exception\PotionException
   */
  public function merge($original, array $files) {
    // Don't process when the original file does not exist.
    if (!file_exists($original)) {
      return FALSE;
    }

    // Check for existing source with valid content.
    if (!$this->isValidPo($original)) {
      throw PotionException::invalidPo($original);
    }

    // Create an incremental backup of original file.
    $backup = $this->backup($original);

    // Add the $original file to the list of $files to merge.
    array_unshift($files, $backup);

    // Remove headers POT-Creation-Date & PO-Revision-Date
    // when merge to avoid conflict.
    foreach ($files as $file) {
      // Read the file line by line to rewrite it whitout incrimined lines.
      $lines = [];
      $read = fopen($file, 'r');
      while (!feof($read)) {
        $lines[] = fgets($read);
      }
      fclose($read);

      // Rewrite the file line by line.
      $write = fopen($file, 'w');
      foreach ($lines as $line) {
        if (substr($line, 0, 19) !== '"POT-Creation-Date:' && substr($line, 0, 18) !== '"PO-Revision-Date:') {
          fwrite($write, $line);
        }
      }
      fclose($write);
    }

    // Merge all $files into the $original output.
    return $this->gettextWrapper->msgcat($files, $original);
  }

  /**
   * Backup the given original file using an incremental suffix.
   *
   * @param string $original
   *   The original PO file.
   *
   * @return string
   *   The backup file uri.
   */
  public function backup($original) {
    // Don't process when the original file don't exists.
    if (!file_exists($original)) {
      return FALSE;
    }

    // Create an incremental backup of original file.
    $backup = $original;
    $suffix = 0;
    while (file_exists($backup)) {
      $backup = $original . '.~' . ++$suffix . '~';
    }
    // Save the original file as backup file.
    copy($original, $backup);

    return $backup;
  }

}
