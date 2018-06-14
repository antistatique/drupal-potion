<?php

namespace Drupal\potion\Extractor;

use Drupal\potion\MessageCatalogue;

/**
 * Base class for Extractors.
 */
abstract class ExtractorBase {

  /**
   * The catalogue of messages.
   *
   * @var \Drupal\potion\MessageCatalogue
   */
  protected $catalogue;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->catalogue = new MessageCatalogue();
  }

}
