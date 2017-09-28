<?php

namespace Drupal\potion\Twig\Extension;

use Drupal\potion\Twig\NodeVisitor\TranslationNodeVisitor;

/**
 *
 */
class TwigTranslationExtractorExtension extends \Twig_Extension {
  /**
   * @var \Drupal\potion\Twig\NodeVisitor\TranslationNodeVisitor
   */
  protected $translationNodeVisitor;

  /**
   * TwigTranslationExtractorExtension constructor.
   */
  public function __construct() {
    $this->translationNodeVisitor = new TranslationNodeVisitor();
  }

  /**
   * Returns the name of the extension.
   *
   * @return string The extension name
   *
   * @deprecated since 1.26 (to be removed in 2.0), not used anymore internally
   */
  public function getName() {
    return 'potion_translation_extractor';
  }

  /**
   *
   */
  public function getNodeVisitors() {
    return [$this->translationNodeVisitor];
  }

  /**
   * Expose the translation Node Visitor to be accessible by the extractor.
   *
   * @return \Drupal\potion\Twig\NodeVisitor\TranslationNodeVisitor
   */
  public function getTranslationNodeVisitor() {
    return $this->translationNodeVisitor;
  }

}
