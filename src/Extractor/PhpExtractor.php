<?php

namespace Drupal\potion\Extractor;

use Symfony\Component\Finder\Finder;
use Drupal\potion\Exception\ExtractorException;
use Symfony\Component\Translation\Extractor\PhpStringTokenParser;
use Drupal\potion\Utility;

/**
 * Extract Translations from PHP files.
 *
 * Based on the Translation component of Symfony.
 *
 * @see \Symfony\Component\Translation\Extractor\PhpExtractor
 */
class PhpExtractor implements ExtractorInterface {

  const MESSAGE_TOKEN = 300;
  const MESSAGE_PLURAL_TOKEN = 301;
  const METHOD_ARGUMENTS_TOKEN = 1000;
  const METHOD_OPTIONS_TOKEN = 1001;

  /**
   * The sequence that captures translation messages.
   *
   * @var array
   */
  protected $sequences = [
      [
        'TranslatableMarkup',
        '(',
        self::MESSAGE_TOKEN,
        ',',
        self::METHOD_ARGUMENTS_TOKEN,
        ',',
        self::METHOD_OPTIONS_TOKEN,
      ],
      [
        'PluralTranslatableMarkup',
        '(',
        self::METHOD_ARGUMENTS_TOKEN,
        ',',
        self::MESSAGE_TOKEN,
        ',',
        self::MESSAGE_PLURAL_TOKEN,
        ',',
        self::METHOD_ARGUMENTS_TOKEN,
        ',',
        self::METHOD_OPTIONS_TOKEN,
      ],
      [
        't',
        '(',
        self::MESSAGE_TOKEN,
        ',',
        self::METHOD_ARGUMENTS_TOKEN,
        ',',
        self::METHOD_OPTIONS_TOKEN,
      ],
      [
        'dt',
        '(',
        self::MESSAGE_TOKEN,
        ',',
        self::METHOD_ARGUMENTS_TOKEN,
        ',',
        self::METHOD_OPTIONS_TOKEN,
      ],
      [
        '->',
        't',
        '(',
        self::MESSAGE_TOKEN,
        ',',
        self::METHOD_ARGUMENTS_TOKEN,
        ',',
        self::METHOD_OPTIONS_TOKEN,
      ],
      [
        '->',
        'trans',
        '(',
        self::MESSAGE_TOKEN,
        ',',
        self::METHOD_ARGUMENTS_TOKEN,
        ',',
        self::METHOD_OPTIONS_TOKEN,
      ],
      [
        '->',
        'translate',
        '(',
        self::MESSAGE_TOKEN,
        ',',
        self::METHOD_ARGUMENTS_TOKEN,
        ',',
        self::METHOD_OPTIONS_TOKEN,
      ],
       [
         '->',
         'formatPlural',
         '(',
         self::METHOD_ARGUMENTS_TOKEN,
         ',',
         self::MESSAGE_TOKEN,
         ',',
         self::MESSAGE_PLURAL_TOKEN,
         ',',
         self::METHOD_ARGUMENTS_TOKEN,
         ',',
         self::METHOD_OPTIONS_TOKEN,
       ],
      [
        '->',
        'trans',
        '(',
        self::MESSAGE_TOKEN,
      ],
  ];

  /**
   * The Utility service of Potion.
   *
   * @var \Drupal\potion\Utility
   */
  protected $utility;

  /**
   * Constructor.
   *
   * @param \Drupal\potion\Utility $utility
   *   Utility methods for Potion.
   */
  public function __construct(Utility $utility) {
    $this->utility = $utility;
  }

  /**
   * {@inheritdoc}
   */
  public function extract($path, $recursive = FALSE) {
    // Collection of unique translations strings.
    $translations = [];

    $files = $this->getFilesFromDirectory($path, $recursive);

    foreach ($files as $file) {
      try {
        // Attempts to extracts translations key from the file.
        $trans = $this->extractFromFile($file->getContents());
        $translations = array_merge($translations, $trans);
      }
      catch (Twig_Error $e) {
        throw new ExtractorException($e->getMessage(), $e->getCode(), $e);
      }
    }
    // Some files could capture the same translations, so uniquify the whole.
    return array_unique($translations);
  }

  /**
   * Extract from a PHP file.
   *
   * @param string $file
   *   File content.
   *
   * @return \Drupal\Component\Gettext\PoItem[]
   *   list of translation messages key extracted from file.
   */
  protected function extractFromFile($file) {
    $translations = [];
    $tokens = token_get_all($file);
    $tokenIterator = new \ArrayIterator($tokens);

    for ($key = 0; $key < $tokenIterator->count(); ++$key) {
      foreach ($this->sequences as $sequence) {
        // Captured message string.
        $message = NULL;
        // Optionnal captured context string.
        $context = NULL;

        // Go th the next token key.
        $tokenIterator->seek($key);
        foreach ($sequence as $item) {
          $this->seekToNextRelevantToken($tokenIterator);

          if ($this->normalizeToken($tokenIterator->current()) === $item) {
            $tokenIterator->next();
          }
          elseif (self::MESSAGE_TOKEN === $item) {
            $message = $this->getValue($tokenIterator);
          }
          elseif (self::MESSAGE_PLURAL_TOKEN === $item) {
            $message = [
              0 => $message,
              1 => $this->getValue($tokenIterator),
            ];
          }
          elseif (self::METHOD_ARGUMENTS_TOKEN === $item) {
            $this->skipMethodArgument($tokenIterator);
            continue;
          }
          elseif (self::METHOD_OPTIONS_TOKEN === $item) {
            $context = $this->getContext($tokenIterator);
            break;
          }
          else {
            break;
          }
        }

        // If message has been captured, save it as PoItem.
        if ($message) {
          $translations = array_merge($translations, $this->utility->setItem($message, $context));
          break;
        }
      }
    }

    return $translations;
  }

  /**
   * Normalizes a token.
   *
   * @param mixed $token
   *   The token to normalize.
   *
   * @return string
   *   the normalized token when needed to be normalized.
   */
  protected function normalizeToken($token) {
    if (isset($token[1])) {
      return $token[1];
    }
    return $token;
  }

  /**
   * Seeks to the next non-whitespace token.
   *
   * @param \Iterator $tokenIterator
   *   The token Iterator.
   */
  private function seekToNextRelevantToken(\Iterator $tokenIterator) {
    for (; $tokenIterator->valid(); $tokenIterator->next()) {
      $t = $tokenIterator->current();
      if (T_WHITESPACE !== $t[0]) {
        break;
      }
    }
  }

  /**
   * Skip to the next then end of method or array arguments.
   *
   * @param \Iterator $tokenIterator
   *   The token Iterator.
   */
  private function skipMethodArgument(\Iterator $tokenIterator) {
    $openBraces = 0;

    for (; $tokenIterator->valid(); $tokenIterator->next()) {
      $t = $tokenIterator->current();

      if ('[' === $t[0] || '(' === $t[0]) {
        ++$openBraces;
      }

      if (']' === $t[0] || ')' === $t[0]) {
        --$openBraces;
      }

      if ((0 === $openBraces && ',' === $t[0]) || (-1 === $openBraces && ')' === $t[0])) {
        break;
      }
    }
  }

  /**
   * Get the context value & skip to the end of array argument.
   *
   * @param \Iterator $tokenIterator
   *   The token Iterator.
   *
   * @return string
   *   The caputred context string.
   */
  private function getContext(\Iterator $tokenIterator) {
    $context_found = FALSE;
    $context = '';
    $openBraces = 0;

    for (; $tokenIterator->valid(); $tokenIterator->next()) {
      $t = $tokenIterator->current();

      // Detect the end of the options arugments.
      if ('[' === $t[0] || '(' === $t[0]) {
        ++$openBraces;
      }
      if (']' === $t[0] || ')' === $t[0]) {
        --$openBraces;
      }
      if ((0 === $openBraces && '[' === $t[0]) || (-1 === $openBraces && ')' === $t[0])) {
        break;
      }

      // Detect the start of 'context' key in the option array arugment.
      if ($this->normalizeToken($t) === "'context'") {
        $context_found = TRUE;
        continue;
      }

      // When we detect the 'context', capture the context string.
      if ($context_found) {
        switch ($t[0]) {
          case T_WHITESPACE:
          case T_DOUBLE_ARROW:
            continue;

          break;
          case T_CONSTANT_ENCAPSED_STRING:
            $context = $t[1];
            $context = PhpStringTokenParser::parse($context);
            return $context;

          break;
          default:
            break;
        }
      }
    }
  }

  /**
   * Extracts the message from the iterator.
   *
   * Capture the message While the tokens match allowed message tokens.
   *
   * @param \Iterator $tokenIterator
   *   The token Iterator.
   *
   * @return string
   *   The message.
   */
  private function getValue(\Iterator $tokenIterator) {
    $message = '';
    $docToken = '';

    for (; $tokenIterator->valid(); $tokenIterator->next()) {
      $t = $tokenIterator->current();
      if (!isset($t[1])) {
        break;
      }

      switch ($t[0]) {
        case T_START_HEREDOC:
          $docToken = $t[1];
          break;

        case T_ENCAPSED_AND_WHITESPACE:
        case T_CONSTANT_ENCAPSED_STRING:
          $message .= $t[1];
          break;

        case T_END_HEREDOC:
          return PhpStringTokenParser::parseDocString($docToken, $message);

        default:
          break 2;
      }
    }

    if ($message) {
      $message = PhpStringTokenParser::parse($message);
    }

    return $message;
  }

  /**
   * Lookup for php files in the directory.
   *
   * @param string $directory
   *   Directory to dig in.
   * @param bool $recursive
   *   Enable or disable scan with recusrsion.
   *
   * @return \Iterator|SplFileInfo[]
   *   An iterator.
   */
  private function getFilesFromDirectory($directory, $recursive = FALSE) {
    $finder = new Finder();
    $finder->files()->name('*.php');
    $finder->files()->name('*.inc');
    $finder->files()->name('*.module');
    $finder->files()->name('*.install');
    if (!$recursive) {
      $finder->depth('== 0');
    }
    return $finder->in($directory);
  }

}
