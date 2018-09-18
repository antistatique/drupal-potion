<?php

namespace Drupal\potion\Extractor;

use Symfony\Component\Finder\Finder;
use Drupal\potion\Exception\ExtractorException;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Drupal\potion\Utility;
use Doctrine\Common\Reflection\StaticReflectionParser;
use Drupal\Component\Annotation\Reflection\MockFileFinder;
use Symfony\Component\Finder\SplFileInfo;
use Doctrine\Common\Annotations\AnnotationRegistry;

/**
 * Extract Annotation Translations from PHP files.
 *
 * The SimpleAnnotationReader is under refactoring this class may drasticaly
 * change soon @see https://www.drupal.org/project/drupal/issues/2631202
 */
class AnnotationExtractor extends ExtractorBase implements ExtractableInterface {
  /**
   * The Utility service of Potion.
   *
   * @var \Drupal\potion\Utility
   */
  protected $utility;

  /**
   * The doctrine annotation reader.
   *
   * @var \Doctrine\Common\Annotations\Reader
   */
  protected $annotationReader;

  /**
   * Constructor.
   *
   * @param \Drupal\potion\Utility $utility
   *   Utility methods for Potion.
   */
  public function __construct(Utility $utility) {
    parent::__construct();

    $this->utility = $utility;

    // Init the annotation reader.
    $this->annotationReader = new SimpleAnnotationReader();
    // Add the Core annotation classes like @Translation & @PluralTranslation.
    $this->annotationReader->addNamespace('Drupal\Core\Annotation');
    AnnotationRegistry::registerLoader('class_exists');
  }

  /**
   * {@inheritdoc}
   */
  public function extract($path, $recursive = FALSE) {
    $files = $this->getFilesFromDirectory($path, $recursive);

    foreach ($files as $file) {
      try {
        // Attempts to extracts translations key from the file.
        $this->extractFromFile($file);
      }
      catch (Twig_Error $e) {
        throw new ExtractorException($e->getMessage(), $e->getCode(), $e);
      }
    }

    return $this->catalogue;
  }

  /**
   * Extract from a Annocation Class file & store it in the catalogue.
   *
   * @param \Symfony\Component\Finder\SplFileInfo $file
   *   The file to process File.
   */
  protected function extractFromFile(SplFileInfo $file) {
    // Get the fully-qualified class name.
    $class = $this->getClassName($file);

    // The filename is already known, so there is no need to find the
    // file. However, StaticReflectionParser needs a finder, so use a
    // mock version.
    // @see \Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery
    $finder = MockFileFinder::create($file->getPathName());
    $parser = new StaticReflectionParser($class, $finder, TRUE);

    // Get all annotations from the Class.
    if ($annotations = $this->annotationReader->getClassAnnotations($parser->getReflectionClass())) {
      foreach ($annotations as $annot) {
        $message = NULL;
        $context = NULL;

        // Extract only annotation that captures translation messages.
        switch (get_class($annot)) {
          case 'Drupal\Core\Annotation\Translation':
            $trans   = $annot->get();
            $message = $trans->getUntranslatedString();
            $context = $trans->getOption('context');
            break;

          case 'Drupal\Core\Annotation\PluralTranslation':
            $trans   = $annot->get();
            $message = [$trans['singular'], $trans['plural']];
            $context = $trans['context'];
            break;

          default:
            break;
        }

        if ($message) {
          $this->catalogue->add($message, $context);
        }
      }
    }
  }

  /**
   * Extract the fully-qualified class name of a php file class.
   *
   * @param \Symfony\Component\Finder\SplFileInfo $file
   *   The file to process File.
   *
   * @return string
   *   The fully-qualified class name.
   */
  private function getClassName(SplFileInfo $file) {
    $namespace = NULL;
    $class = NULL;

    // Helper values to know that we have found the namespace/class token.
    $getting_namespace = $getting_class = FALSE;

    // Go through each token and evaluate it as necessary.
    foreach (token_get_all($file->getContents()) as $token) {

      // When token is the namespace declaration, flag that the next tokens
      // will be the namespace name.
      if (is_array($token) && $token[0] == T_NAMESPACE) {
        $getting_namespace = TRUE;
        continue;
      }

      // When token is the class declaration, flag that the next tokens
      // will be the class name.
      if (is_array($token) && $token[0] == T_CLASS) {
        $getting_class = TRUE;
        continue;
      }

      // While grabbing the namespace name...
      if ($getting_namespace === TRUE) {
        // When token is a string or the namespace separator save it until
        // reaching the semicolon.
        if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR])) {
          // Append the token's value to the name of the namespace.
          $namespace .= $token[1];
        }
        elseif ($token === ';') {
          // When token is the semicolon, it's the end of namespace declaration.
          $getting_namespace = FALSE;
        }

        continue;
      }

      // While grabbing the class name and token is a string, it's the class.
      if ($getting_class === TRUE && is_array($token) && $token[0] == T_STRING) {
        $class = $token[1];
        break;
      }
    }

    // Build the fully-qualified class name.
    return $namespace ? $namespace . '\\' . $class : $class;
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
    if (!$recursive) {
      $finder->depth('== 0');
    }
    return $finder->in($directory);
  }

}
