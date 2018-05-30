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

  /**
   * Invalid mode for importation has been given.
   *
   * @param string $mode
   *   The invalid mode given.
   *
   * @return PotionException
   *   The invalid mode exception.
   */
  public static function invalidMode($mode) {
    return new static('Mode ' . $mode . ' is invalid. Only "customized" & "non-customized" are allowed.');
  }

  /**
   * The destination path or file does not exist.
   *
   * @param string $path
   *   The file or path.
   *
   * @return PotionException
   *   Exception because of unexisting path or file.
   */
  public static function notFound($path) {
    return new static('No such file or directory ' . $path);
  }

  /**
   * The path is not writable.
   *
   * @param string $path
   *   The path.
   *
   * @return PotionException
   *   Exception because the path is not writable.
   */
  public static function isNotWritable($path) {
    return new static('The path ' . $path . ' is not writable.');
  }

  /**
   * The path is not writable.
   *
   * @param string $path
   *   The path.
   *
   * @return PotionException
   *   Exception because the path is not readable.
   */
  public static function isNotReadable($path) {
    return new static('The path ' . $path . ' is not readable.');
  }

}
