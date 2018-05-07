<?php

namespace Drupal\potion;

use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\Process\Process;

/**
 * Contains utility methods for the Potion module.
 */
class Utility {
  const GETTEXT_VERSION = '0.19.8.1';

  /**
   * The language Manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManager
   */
  protected $languageManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
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
   */
  public function isValidPo($src) {
    $msgfmt = __DIR__ . '/../scripts/gettext/' . self::GETTEXT_VERSION . '/bin/msgfmt';

    if (!is_file($src)) {
      return FALSE;
    }

    try {
      $cmd = $msgfmt . ' --check ' . $src;

      $process = new Process($cmd);
      $process->run();
    } catch (\Exception $e) {
      $this->fail($e);
    }

    if ($process->getExitCode() > 0) {
      return FALSE;
    }

    return TRUE;
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
