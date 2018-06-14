<?php

namespace Drupal\potion\Extractor;

use Twig_Environment;
use Twig_Error;
use Twig_Source;
use Symfony\Component\Finder\Finder;
use Drupal\potion\Exception\ExtractorException;

/**
 * Extract Translations from Twig template.
 */
class TwigExtractor extends ExtractorBase implements ExtractableInterface {

  /**
   * The twig environment.
   *
   * @var \Twig_Environment
   */
  private $twig;

  /**
   * Constructor.
   *
   * @param \Twig_Environment $twig
   *   Twig Env.
   */
  public function __construct(Twig_Environment $twig) {
    parent::__construct();

    $this->twig = $twig;
  }

  /**
   * {@inheritdoc}
   */
  public function extract($path, $recursive = FALSE) {
    $files = $this->getFilesFromDirectory($path, $recursive);
    foreach ($files as $file) {
      try {
        // Attempts to extracts translations key from the template.
        $file_catalogue = $this->extractFromTemplate($file->getContents());
        $this->catalogue->merge($file_catalogue);
      }
      catch (Twig_Error $e) {
        throw new ExtractorException($e->getMessage(), $e->getCode(), $e);
      }
    }

    return $this->catalogue;
  }

  /**
   * Extract from a Twig template & store it in the catalogue.
   *
   * @param string $template
   *   Twig content template.
   *
   * @return \Drupal\potion\MessageCatalogue
   *   Catalogue of extracted translations messages.
   */
  protected function extractFromTemplate($template) {
    /** @var \Drupal\potion\Twig\NodeVisitor\TranslationNodeVisitor $visitor */
    $visitor = $this->twig->getExtension('\Drupal\potion\Twig\Extension\TransExtractorExtension')
      ->getTranslationNodeVisitor();
    $visitor->enable();

    $this->twig->parse($this->twig->tokenize(new Twig_Source($template, '')));
    $visitor->disable();

    return $visitor->getCatalogue();
  }

  /**
   * Lookup for twig files in the directory.
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
    $finder->files()->name('*.twig');
    if (!$recursive) {
      $finder->depth('== 0');
    }
    return $finder->in($directory);
  }

}
