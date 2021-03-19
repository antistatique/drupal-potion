<?php

namespace Drupal\potion\Twig\Extension;

use Drupal\potion\Twig\NodeVisitor\TranslationNodeVisitor;

/**
 * Provide the potion translations extractor extension.
 *
 * This provides a Twig extension that registers node visitors to extract
 * various translations strings.
 *
 * @see \Drupal\Core\CoreServiceProvider
 */
class TransExtractorExtension extends \Twig_Extension {
  /**
   * The NodeVisitor to extracts translation messages from twig.
   *
   * @var \Drupal\potion\Twig\NodeVisitor\TranslationNodeVisitor
   */
  protected $transNodeVisitor;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->transNodeVisitor = new TranslationNodeVisitor();
  }

  /**
   * Returns the node visitor instances to add to the existing list.
   *
   * @return Twig_NodeVisitorInterface[]
   *   A collection of NodeVisitor.
   */
  public function getNodeVisitors() {
    return [$this->transNodeVisitor];
  }

  /**
   * Expose the translation Node Visitor to be accessible by the extractor.
   *
   * @return \Drupal\potion\Twig\NodeVisitor\TranslationNodeVisitor
   *   The NodeVisitor to extracts translation messages from twig.
   */
  public function getTranslationNodeVisitor() {
    return $this->transNodeVisitor;
  }

}
