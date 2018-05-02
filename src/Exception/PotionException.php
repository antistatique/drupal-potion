<?php

namespace Drupal\potion\Exception;

/**
 * Represents an exception that occurred in some part of Potion.
 */
class PotionException extends \Exception {

  /**
   * Invalid langcode exception.
   *
   * @param string $langcode
   *   The ISO langcode which is invalid.
   *
   * @return PotionException
   *   The invalid langcode exception.
   */
  public static function invalidLangcode($langcode) {
    return new static('The langcode ' . $langcode . ' is not defined. Please create & enabled it before trying to use it.');
  }

  /**
   * Invalid po file exception.
   *
   * @param string $file
   *   The po file which is invalid/malformed.
   *
   * @return PotionException
   *   The invalid po file exception.
   */
  public static function invalidPo($file) {
    return new static('File ' . $file . ' is a malformed .po file.');
  }

}
