<?php

namespace Drupal\potion\Extractor;

/**
 * Interface TranslationExtractorInterface.
 */
interface TranslationExtractorInterface {

  /**
   * Extract translations string.
   *
   * @param string $path
   *   Base path directory to lookup for files.
   * @param bool $recursive
   *   Does the extractor should recursively lookup for files.
   *
   * @return string[]
   *   Collection of translations keys
   */
  public function extract($path, $recursive = FALSE);

}
