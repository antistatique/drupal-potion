<?php

namespace Drupal\potion\Twig\NodeVisitor;

use Drupal\Core\Template\TwigNodeTrans;
use Twig\NodeVisitor\AbstractNodeVisitor;
use Drupal\potion\MessageCatalogue;

/**
 * Extracts translation messages from twig.
 *
 * Inspired from Symfony TwigBridge
 * https://github.com/symfony/twig-bridge/blob/2.8/NodeVisitor/TranslationNodeVisitor.php.
 *
 * @see \Drupal\Core\Template\TwigNodeTrans
 */
class TranslationNodeVisitor extends AbstractNodeVisitor {
  /**
   * Define the visitor state - enabled or disabled.
   *
   * @var bool
   */
  private $enabled = FALSE;

  /**
   * The catalogue of messages.
   *
   * @var \Drupal\potion\MessageCatalogue
   */
  protected $catalogue;

  /**
   * The Utility service of Potion.
   *
   * @var \Drupal\potion\Utility
   */
  protected $utility;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->utility = \Drupal::service('potion.utility');
  }

  /**
   * Enable the visitor.
   */
  public function enable() {
    $this->enabled = TRUE;
    $this->catalogue = new MessageCatalogue();
  }

  /**
   * Disable the visitor.
   */
  public function disable() {
    $this->enabled = FALSE;
  }

  /**
   * Return the list of translation messages key extracted from Twig.
   *
   * @return \Drupal\potion\MessageCatalogue
   *   Catalogue of extracted translations messages.
   */
  public function getCatalogue() {
    return $this->catalogue;
  }

  /**
   * {@inheritdoc}
   */
  protected function doEnterNode(\Twig_Node $node, \Twig_Environment $env) {
    if (!$this->enabled) {
      return $node;
    }

    // If we are on a `|trans` or `|t`.
    if (
      $node instanceof \Twig_Node_Expression_Filter &&
      in_array($node->getNode('filter')->getAttribute('value'), ['trans', 't']) &&
      $node->getNode('node') instanceof \Twig_Node_Expression_Constant
    ) {
      // Save the extracted translations in the messages collection.
      $this->catalogue->add($node->getNode('node')->getAttribute('value'));

      return $node;
    }

    // Unexpected behavior, close the visitor here.
    if (!$node instanceof TwigNodeTrans) {
      return $node;
    }

    // If we arrive at this point, we deal with non-filter case
    // Eg. `%trans%`, plural, multilines, ...
    // Get context on non-filter case (`%trans%`, plural, multilines, ...).
    $context = NULL;
    if ($node->hasNode('options') && $node->getNode('options') instanceof \Twig_Node_Expression_Array) {
      $context = $this->getContext($node->getNode('options'));
    }

    // If we are on a simple `% trans '' %` whitout token or plural form.
    // Eg. `{% trans 'Hello sun' %}`.
    if ($node->getNode('body')->hasAttribute('value') &&
      (!$node->hasNode('plural') || is_null($node->getNode('plural')))
    ) {
      // Save the extracted translations in the messages collection.
      $this->catalogue->add($node->getNode('body')->getAttribute('value'), $context);
      return $node;
    }

    // If we are on a simple `% trans %` whitout token or plural form.
    // Eg. `{% trans %}Hello moon.{% endtrans %}`.
    if ($node->getNode('body')->hasAttribute('data') &&
      (!$node->hasNode('plural') || is_null($node->getNode('plural')))
    ) {
      // Save the extracted translations in the messages collection.
      $this->catalogue->add($node->getNode('body')->getAttribute('data'), $context);

      return $node;
    }

    // Complex code block with token, multilines whitout plural.
    // Eg. `{% trans %}Hello moon {{ node.id }}{% endtrans %}`.
    if (!$node->hasNode('plural') || is_null($node->getNode('plural'))) {
      $message = '';
      if ($node->getNode('body')->hasAttribute('data')) {
        $message .= $node->getNode('body')->getAttribute('data');
      }

      $message .= $this->compileString($node->getNode('body'));
      // Save the extracted translations in the messages collection.
      $this->catalogue->add($message, $context);
      return $node;
    }

    // Complex code block with token, multilines with plural.
    /* Eg. `{% trans %}Hello moon
     *      {% plural count %}Hello {{ count }} moons.{{ node.id }}
     *      {% endtrans %}`.
     */
    if ($node->hasNode('plural') && $node->getNode('plural') instanceof \Twig_Node) {
      $singular = '';
      if ($node->getNode('body')->hasAttribute('data')) {
        $singular .= $node->getNode('body')->getAttribute('data');
      }
      $singular .= $this->compileString($node->getNode('body'));
      $plural = $this->compileString($node->getNode('plural'));
      // Save the extracted translations in the messages collection.
      $this->catalogue->add([0 => trim($singular), 1 => trim($plural)], $context);

      return $node;
    }

    return $node;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLeaveNode(\Twig_Node $node, \Twig_Environment $env) {
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority() {
    return 0;
  }

  /**
   * Extracts the text for complexe form of "trans" tag.
   *
   * @param \Twig_Node $body
   *   The node to compile.
   *
   * @return string
   *   The translations strings.
   *
   * @see \Drupal\Core\Template\TwigNodeTrans::compileString
   */
  protected function compileString(\Twig_Node $body) {
    $message = '';

    foreach ($body as $node) {
      if (get_class($node) === 'Twig_Node' && $node->getNode(0) instanceof \Twig_Node_SetTemp) {
        $node = $node->getNode(1);
      }

      if ($node instanceof \Twig_Node_Print) {
        $n = $node->getNode('expr');
        while ($n instanceof \Twig_Node_Expression_Filter) {
          $n = $n->getNode('node');
        }

        $args = $n;

        // Support TwigExtension->renderVar() function in chain.
        if ($args instanceof \Twig_Node_Expression_Function) {
          $args = $n->getNode('arguments')->getNode(0);
        }

        // Detect if a token implements one of the filters reserved for
        // modifying the prefix of a token. The default prefix used for
        // translations is "@". This escapes the printed token and makes
        // them // safe for templates.
        // @see TwigExtension::getFilters()
        $argPrefix = '@';
        while ($args instanceof \Twig_Node_Expression_Filter) {
          switch ($args->getNode('filter')->getAttribute(
            'value'
          )) {
            case 'placeholder':
              $argPrefix = '%';
              break;
          }
          $args = $args->getNode('node');
        }
        if ($args instanceof \Twig_Node_Expression_GetAttr) {
          $argName = [];
          // Assemble a valid argument name by walking through expression.
          $argName[] = $args->getNode('attribute')
            ->getAttribute('value');
          while ($args->hasNode('node')) {
            $args = $args->getNode('node');
            if ($args instanceof \Twig_Node_Expression_Name) {
              $argName[] = $args->getAttribute('name');
            }
            else {
              $argName[] = $args->getNode('attribute')
                ->getAttribute('value');
            }
          }
          $argName = array_reverse($argName);
          $argName = implode('.', $argName);
        }
        else {
          $argName = $n->getAttribute('name');
          if (!is_null($args)) {
            $argName = $args->getAttribute('name');
          }
        }
        $placeholder = sprintf('%s%s', $argPrefix, $argName);
        $message .= $placeholder;
      }
      else {
        $message .= $node->getAttribute('data');
      }
    }

    return $message;
  }

  /**
   * Retrieive the context values from a NodeExpression array.
   *
   * @param \Twig_Node_Expression_Array $options
   *   A collection of \Twig_Node_Expression_Constant.
   *
   * @return string
   *   The context this translation belongs to.
   */
  protected function getContext(\Twig_Node_Expression_Array $options) {
    $args = $options->getKeyValuePairs();
    foreach ($args as $pair) {
      if ($pair['key']->getAttribute('value') == 'context') {
        return $pair['value']->getAttribute('value');
      }
    }
  }

}
