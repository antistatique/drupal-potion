<?php

namespace Drupal\potion\Exception;

/**
 * Represents an exception that occurred in some part of Gettext.
 */
class GettextException extends PotionException {

  /**
   * Command not found.
   *
   * @param string $cmd
   *   The command to execute.
   *
   * @return PotionException
   *   The invalid command to execute.
   */
  public static function commandNotFound($cmd) {
    return new static("Command not found: $cmd");
  }

}
