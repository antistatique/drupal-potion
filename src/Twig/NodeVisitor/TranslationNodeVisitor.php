<?php

namespace Drupal\potion\Twig\NodeVisitor;

use Drupal\Core\Template\TwigNodeTrans;
use Twig\NodeVisitor\AbstractNodeVisitor;

/**
 * TranslationNodeVisitor extracts translation messages.
 *
 * Inspired from Symfony TwigBridge
 * https://github.com/symfony/twig-bridge/blob/2.8/NodeVisitor/TranslationNodeVisitor.php.
 */
class TranslationNodeVisitor extends AbstractNodeVisitor {
  private $enabled = FALSE;
  private $messages = [];

  /**
   * Enable the visitor.
   */
  public function enable() {
    $this->enabled = TRUE;
    $this->messages = [];
  }

  /**
   * Disable the visitor.
   */
  public function disable() {
    $this->enabled = FALSE;
    $this->messages = [];
  }

  /**
   * Return the list of translation messages key extracted from Twig.
   *
   * @return array
   *   string translations keys.
   */
  public function getMessages() {
    return $this->messages;
  }

  /**
   * {@inheritdoc}
   */
  protected function doEnterNode(\Twig_Node $node, \Twig_Environment $env) {
    if (!$this->enabled) {
      return $node;
    }

    if (
      $node instanceof \Twig_Node_Expression_Filter &&
      in_array($node->getNode('filter')->getAttribute('value'), ['trans', 't']) &&
      $node->getNode('node') instanceof \Twig_Node_Expression_Constant
    ) {
      // Extract constant nodes with a trans filter.
      $this->messages[] = $node->getNode('node')->getAttribute('value');
    }
    elseif (
      $node instanceof \Twig_Node_Expression_Filter &&
      'transchoice' === $node->getNode('filter')->getAttribute('value') &&
      $node->getNode('node') instanceof \Twig_Node_Expression_Constant
    ) {
      // Extract constant nodes with a trans filter.
      $this->messages[] = $node->getNode('node')->getAttribute('value');
    }
    elseif ($node instanceof TwigNodeTrans) {

      $body = $node->getNode('body');
      $message = NULL;

      if ($node->getNode('body')->hasAttribute('data')) {
        $message = $node->getNode('body')->getAttribute('data');
      }
      else {
        // Complex code block like `{% trans %} my.string {{ node.value }} {% endtrans %}`
        // code copied from Drupal\Core\Template\TwigNodeTrans::compileString.
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
            // translations is "@". This escapes the printed token and makes them
            // safe for templates.
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
              // Assemble a valid argument name by walking through the expression.
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
      }

      // Extract trans nodes.
      $this->messages[] = $message;
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

}
