<?php

namespace Drupal\potion;

use Drupal\Core\Language\LanguageManagerInterface;

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
   * Class constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\potion\GettextWrapper $gettext_wrapper
   *   The Gettext wrapper.
   */
  public function __construct(LanguageManagerInterface $language_manager, GettextWrapper $gettext_wrapper) {
    $this->languageManager = $language_manager;
    $this->gettextWrapper = $gettext_wrapper;
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

}
