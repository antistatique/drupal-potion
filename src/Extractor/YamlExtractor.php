<?php

namespace Drupal\potion\Extractor;

use Symfony\Component\Finder\Finder;
use Drupal\potion\Exception\ExtractorException;
use Drupal\potion\Utility;
use Symfony\Component\Finder\SplFileInfo;
use Drupal\Component\Serialization\SerializationInterface;

/**
 * Extract Translations from YAML files.
 *
 * Here are the YAML keys which are found as translatable:
 * - "name" and "description" in *.info.yml
 * - "_title" coupled with optional "_title_context" in *.routing.yml
 * - "title" coupled with optional "title_context in *.links.action.yml,
 * *.links.task.yml and *.links.contextual.yml.
 *
 * @see https://www.drupal.org/docs/8/api/translation-api/overview
 */
class YamlExtractor implements ExtractorInterface {

  /**
   * The Utility service of Potion.
   *
   * @var \Drupal\potion\Utility
   */
  protected $utility;

  /**
   * Provides a YAML serialization implementation.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $yamlSerializer;

  /**
   * The files suffix with keys that captures translation messages.
   *
   * @var array
   */
  protected $sequencesByFiles = [
    '.info.yml' => [
      [
        'message' => ['name'],
        'context' => NULL,
      ],
      [
        'message' => ['description'],
        'context' => NULL,
      ],
    ],
    '.routing.yml' => [
      [
        'message' => ['defaults', '_title'],
        'context' => ['defaults', '_title_context'],
      ],
    ],
    '.links.action.yml' => [
      [
        'message' => ['title'],
        'context' => ['title_context'],
      ],
    ],
    '.links.task.yml' => [
      [
        'message' => ['title'],
        'context' => ['title_context'],
      ],
    ],
    '.links.contextual.yml' => [
      [
        'message' => ['title'],
        'context' => ['title_context'],
      ],
    ],
  ];

  /**
   * Constructor.
   *
   * @param \Drupal\potion\Utility $utility
   *   Utility methods for Potion.
   * @param \Drupal\Component\Serialization\SerializationInterface $yaml_serialization
   *   Provides a YAML serialization implementation.
   */
  public function __construct(Utility $utility, SerializationInterface $yaml_serialization) {
    $this->utility        = $utility;
    $this->yamlSerializer = $yaml_serialization;
  }

  /**
   * {@inheritdoc}
   */
  public function extract($path, $recursive = FALSE) {
    // Collection of unique translations strings between all files.
    $translations = [];

    $files = $this->getFilesFromDirectory($path, $recursive);

    foreach ($files as $file) {
      try {
        // Attempts to extracts translations key from the file.
        $trans = $this->extractFromFile($file);
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
   * Extract from a Annocation Class file.
   *
   * @param \Symfony\Component\Finder\SplFileInfo $file
   *   The file to process File.
   *
   * @return \Drupal\Component\Gettext\PoItem[]
   *   list of translation messages key extracted from file.
   */
  protected function extractFromFile(SplFileInfo $file) {
    // Collection of unique translations strings for this file.
    $translations = [];

    // Get a multidimensionnal array from Yaml file.
    $data = $this->yamlSerializer->decode($file->getContents());

    $sequences = NULL;
    // Get the sequences of this file.
    foreach ($this->sequencesByFiles as $suffix => $seq) {
      if ($this->endsWith($file->getFileName(), $suffix)) {
        $sequences = $seq;
        break;
      }
    }

    // Do nothing if no sequences exists for this kind of file.
    if (!$sequences) {
      return $translations;
    }

    // Loop throught every top level element of Yaml file.
    foreach ($data as $item) {
      // Run every file sequence on then element or his direct children.
      foreach ($sequences as $sequence) {
        $message = '';
        $context = '';

        // The tree of Yaml to walk, sometimes every keys are on the top lvl.
        $tree = is_array($item) ? $item : $data;

        // Get the message sequence & lookup for it on the Yaml file.
        $msg_sequence = $sequence['message'];
        $message = $this->walk($tree, $msg_sequence);

        // Get the context sequence & lookup for it on the Yaml file.
        if ($sequence['context']) {
          $ctx_sequence = $sequence['context'];
          $context = $this->walk($tree, $ctx_sequence);
        }

        if ($message) {
          $translations = array_merge($translations, $this->utility->setItem($message, $context));
        }
      }
    }

    return $translations;
  }

  /**
   * Recursive method to lookup on value following a given sequence.
   *
   * @param array $tree
   *   The tree to lookup into.
   * @param array $sequence
   *   The complete sequence to use for lookup.
   * @param int $depth
   *   The current depth of the sequence.
   *
   * @return mixed
   *   Return the found value (mixed values) or NULL.
   */
  private function walk(array $tree, array $sequence, $depth = 0) {
    foreach ($tree as $leaf => $value) {
      if ($leaf === $sequence[$depth]) {
        if (!is_array($tree[$leaf]) || !isset($sequence[$depth + 1])) {
          return $tree[$leaf];
        }
        return $this->walk($tree[$leaf], $sequence, ++$depth);
      }
    }

    return NULL;
  }

  /**
   * Check if the given $needle is found at the end of $haystack.
   *
   * @param string $haystack
   *   The string to search into.
   * @param string $needle
   *   The string to search.
   *
   * @return bool
   *   Does thes $needle is found at the end of $haystack - or not.
   */
  private function endsWith($haystack, $needle) {
    $length = mb_strlen($needle, 'UTF-8');
    return $length === 0 ||
        (mb_substr($haystack, -$length, $length, 'UTF-8') === $needle);
  }

  /**
   * Lookup for YAML files in the directory.
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

    foreach ($this->sequencesByFiles as $file => $keys) {
      $finder->files()->name('*' . $file);
    }
    if (!$recursive) {
      $finder->depth('== 0');
    }
    return $finder->in($directory);
  }

}
