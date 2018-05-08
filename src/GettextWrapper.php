<?php

namespace Drupal\potion;

use Symfony\Component\Process\Process;
use Drupal\potion\Exception\GettextException;

/**
 * Contains wrapper methods for the Gettext libraries.
 */
class GettextWrapper {
  const VERSION = '0.19.8.1';
  const BIN = __DIR__ . '/../scripts/gettext/' . self::VERSION . '/bin';

  /**
   * Assert file integrity: format, header, domain.
   *
   * - format:  Asserts translations strings formatted according lang.
   * - header:  Asserts the header exists & is valid.
   * - domain:  Looking for conflicted strings.
   *
   * @param string $src
   *   The file to validate.
   *
   * @return bool
   *   TRUE if the given file is valid, FALSE otherwise.
   *
   * @throws \Drupal\potion\Exception\GettextException
   */
  public static function msgfmt($src) {
    try {
      $cmd = rtrim(self::BIN, '/') . '/msgfmt --check ' . $src;
      $process = new Process($cmd);
      $process->run();
    }
    catch (\Exception $e) {
      throw new GettextException($e->getMessage(), $e->getCode(), $e);
    }

    if ($process->getExitCode() > 0) {
      return FALSE;
    }

    return TRUE;
  }

}
