<?php

namespace Drupal\potion;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\File\FileSystemInterface;

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
   * From a given langcode, retreive the langname.
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
    // Only trim if we're not dealing with a stream.
    if (!file_stream_wrapper_valid_scheme($this->fileSystem->uriScheme($path))) {
      $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
    return $path;
  }

}
