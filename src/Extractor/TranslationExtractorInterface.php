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
   *   base path directory to lookup for Twig files.
   *
   * @return array
   *   of translations keys
   */
  public function extract($path);

}
