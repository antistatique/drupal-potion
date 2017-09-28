<?php

namespace Drupal\potion\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\potion\Extractor\TranslationExtractorInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * In addition to a commandfile like this one, you need to add a drush.services.yml
 * in root of your module.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml.
 */
class PotionCommands extends DrushCommands {

  /**
   * @var TranslationExtractorInterface
   */
  protected $extractor;

  /**
   * PotionCommands constructor.
   * @param \Drupal\potion\Extractor\TranslationExtractorInterface $extractor
   */
  public function __construct(TranslationExtractorInterface $extractor) {
    $this->extractor = $extractor;
  }

  /**
   * Extract Translations
   *
   * @command potion-extract-template
   * @param $path
   *   Directory of your twig templates.
   *
   * @option format output format (allowed values: text)
   * @usage potion-extract-template path/to/templates --output=text
   *   Extract translations key from templates.
   * @aliases pte
   */
  public function translationExtract($path, $options = ['format' => 'text']) {

    if (!$path) {
      $this->logger()->error('Path args is required');
      return;
    }

    $translations = $this->extractor->extract($path);

    $this->output()->writeln($translations);
  }
}
