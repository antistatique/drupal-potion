<?php

namespace Drupal\potion\Extractor;

/**
 * Interface ExtractableInterface.
 */
interface ExtractableInterface {

  /**
   * Extract translations string.
   *
   * @param string $path
   *   Base path directory to lookup for files.
   * @param bool $recursive
   *   Does the extractor should recursively lookup for files.
   *
   * @return \Drupal\potion\MessageCatalogue
   *   Catalogue of extracted translations messages.
   */
  public function extract($path, $recursive = FALSE);

}
