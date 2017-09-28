<?php

namespace Drupal\potion\Extractor;

use Twig_Environment;
use Twig_Error;
use Twig_Source;
// @TODO: Add it to composer or not necessary as already in Drupal Core?
use Symfony\Component\Finder\Finder;

/**
 * Extract Translations from Twig template
 */
class TwigExtractor implements TranslationExtractorInterface
{
  /**
   * The twig environment.
   *
   * @var \Twig_Environment
   */
  private $twig;

  /**
   * TwigExtractor constructor.
   *
   * @param \Twig_Environment $twig
   */
  public function __construct(Twig_Environment $twig)
  {
    $this->twig = $twig;
  }

  /**
   * {@inheritdoc}
   */
  public function extract($path)
  {
    $translations = [];
    $files = $this->extractFiles($path);
    foreach ($files as $file) {

      try {
        $trans = $this->extractTemplate(file_get_contents($file->getPathname()));
        $translations = array_merge($translations, $trans);
      } catch (Twig_Error $e) {
        if ($file instanceof \SplFileInfo) {
          $pathname = $file->getRealPath() ?: $file->getPathname();
          $name = $file instanceof \SplFileInfo ? $file->getRelativePathname() : $pathname;
          if (method_exists($e, 'setSourceContext')) {
            $e->setSourceContext(new Twig_Source('', $name, $pathname));
          } else {
            $e->setTemplateName($name);
          }
        }

        throw $e;
      }
    }

    return array_unique($translations);
  }

  /**
   * @param string|array $resource files, a file or a directory
   *
   * @return array
   */
  private function extractFiles($resource)
  {
    if (is_array($resource) || $resource instanceof \Traversable) {
      $files = array();
      foreach ($resource as $file) {
        if ($this->canBeExtracted($file)) {
          $files[] = $this->toSplFileInfo($file);
        }
      }
    } elseif (is_file($resource)) {
      $files = $this->canBeExtracted($resource) ? array($this->toSplFileInfo($resource)) : array();
    } else {
      $files = $this->extractFromDirectory($resource);
    }

    return $files;
  }

  /**
   * @param $template
   *
   * @return array
   */
  protected function extractTemplate($template)
  {
    /** @var \Drupal\potion\Twig\NodeVisitor\TranslationNodeVisitor $visitor */
    $visitor = $this->twig->getExtension('potion_translation_extractor')->getTranslationNodeVisitor();
    $visitor->enable();

    $this->twig->parse($this->twig->tokenize(new Twig_Source($template, '')));

    $translation = $visitor->getMessages();

    $visitor->disable();

    return $translation;
  }

  /**
   * @param string $file
   *
   * @return \SplFileInfo
   */
  private function toSplFileInfo($file)
  {
    return ($file instanceof \SplFileInfo) ? $file : new \SplFileInfo($file);
  }

  /**
   * @param string $file
   *
   * @return bool
   *
   * @throws \InvalidArgumentException
   */
  private function isFile($file)
  {
    if (!is_file($file)) {
      throw new \InvalidArgumentException(sprintf('The "%s" file does not exist.', $file));
    }

    return true;
  }

  /**
   * @param string $file
   *
   * @return bool
   */
  private function canBeExtracted($file)
  {
    return $this->isFile($file) && 'twig' === pathinfo($file, PATHINFO_EXTENSION);
  }

  /**
   * @param string|array $directory
   *
   * @return mixed
   */
  private function extractFromDirectory($directory)
  {
    $finder = new Finder();

    return $finder->files()->name('*.twig')->in($directory);
  }
}