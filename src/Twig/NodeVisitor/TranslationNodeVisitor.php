<?php

namespace Drupal\potion\Twig\NodeVisitor;

use Drupal\Core\Template\TwigNodeTrans;
use Twig\NodeVisitor\AbstractNodeVisitor;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;

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
   * List of translation messages key extracted from Twig.
   *
   * @var \Drupal\Component\Gettext\PoItem[]
   */
  private $messages = [];

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
   * @return \Drupal\Component\Gettext\PoItem[]
   *   Collection of PoItem translations keys.
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

    // If we are on a `|trans` or `|t`.
    if (
      $node instanceof \Twig_Node_Expression_Filter &&
      in_array($node->getNode('filter')->getAttribute('value'), ['trans', 't']) &&
      $node->getNode('node') instanceof \Twig_Node_Expression_Constant
    ) {
      // Save the extracted translations in the messages collection.
      $this->messages = array_merge($this->messages, $this->utility->setItem($node->getNode('node')->getAttribute('value')));

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
    if ($node->getNode('options')) {
      $context = $this->getContext($node->getNode('options'));
    }

    // If we are on a simple `% trans '' %` whitout token or plural form.
    // Eg. `{% trans 'Hello sun' %}`.
    if ($node->getNode('body')->hasAttribute('value') && is_null($node->getNode('plural'))) {
      // Save the extracted translations in the messages collection.
      $this->messages = array_merge($this->messages, $this->utility->setItem($node->getNode('body')->getAttribute('value'), $context));
      return $node;
    }

    // If we are on a simple `% trans %` whitout token or plural form.
    // Eg. `{% trans %}Hello moon.{% endtrans %}`.
    if ($node->getNode('body')->hasAttribute('data') && is_null($node->getNode('plural'))) {
      // Save the extracted translations in the messages collection.
      $this->messages = array_merge($this->messages, $this->utility->setItem($node->getNode('body')->getAttribute('data'), $context));
      return $node;
    }

    // Complex code block with token, multilines whitout plural.
    // Eg. `{% trans %}Hello moon {{ node.id }}{% endtrans %}`.
    if (is_null($node->getNode('plural'))) {
      $message = '';
      if ($node->getNode('body')->hasAttribute('data')) {
        $message .= $node->getNode('body')->getAttribute('data');
      }

      $message .= $this->compileString($node->getNode('body'));
      // Save the extracted translations in the messages collection.
      $this->messages = array_merge($this->messages, $this->utility->setItem($message, $context));
      return $node;
    }

    // Complex code block with token, multilines with plural.
    /* Eg. `{% trans %}Hello moon
     *      {% plural count %}Hello {{ count }} moons.{{ node.id }}
     *      {% endtrans %}`.
     */
    if (!is_null($node->getNode('plural'))) {
      $singular = '';
      if ($node->getNode('body')->hasAttribute('data')) {
        $singular .= $node->getNode('body')->getAttribute('data');
      }
      $singular .= $this->compileString($node->getNode('body'));
      $plural = $this->compileString($node->getNode('plural'));
      // Save the extracted translations in the messages collection.
      $this->messages = array_merge($this->messages, $this->utility->setItem([0 => trim($singular), 1 => trim($plural)], $context));

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
   * Store the parsed values as a PoItem object.
   *
   * @param string|array $msgid
   *   The translations source string or array of strings if it has plurals.
   * @param string $msgctxt
   *   The context this translation belongs to.
   */
  protected function setItem($msgid, $msgctxt = NULL) {
    // Save source & translations as stinog or array of strings if it's plural.
    $source      = is_array($msgid) ? implode(PluralTranslatableMarkup::DELIMITER, $msgid) : trim($msgid);
    $translation = is_array($msgid) ? implode(PluralTranslatableMarkup::DELIMITER, ['', '']) : '';

    $item = new PoItem();
    $item->setFromArray([
      'context'     => $msgctxt,
      'source'      => $source,
      'translation' => $translation,
      'comment'     => NULL,
    ]);

    // Generate a uniq key by translations to avoid duplicates.
    $id = md5($source . $msgctxt);
    $this->messages[$id] = $item;
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
