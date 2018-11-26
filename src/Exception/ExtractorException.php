<?php

namespace Drupal\potion\Exception;

/**
 * Represents an exception that occurred in some part of an Extractor.
 */
class ExtractorException extends PotionException {

  /**
   * Nothing has been extracted from path.
   *
   * @param string $path
   *   The path.
   *
   * @return PotionException
   *   Exception because nothing has been extracted from path.
   */
  public static function empty($path) {
    return new static('No translations strings found in ' . $path . '.');
  }

}
