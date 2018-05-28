<?php

namespace Drupal\potion\Extractor;

/**
 * Interface ExtractorInterface.
 */
interface ExtractorInterface {

  /**
   * Extract translations string.
   *
   * @param string $path
   *   Base path directory to lookup for files.
   * @param bool $recursive
   *   Does the extractor should recursively lookup for files.
   *
   * @return \Drupal\Component\Gettext\PoItem[]
   *   Collection of translations keys.
   */
  public function extract($path, $recursive = FALSE);
}
