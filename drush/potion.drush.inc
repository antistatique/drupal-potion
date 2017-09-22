<?php

/**
 * @file
 * Drush integration for the potion module.
 */

/**
 * Implements hook_drush_command().
 */
function potion_drush_command() {
  // @todo add description, arguments, options & required-arguments
  $items['potion-extract-template'] = [
    'description' => dt('Extract'),
    'options' => [
      'file' => dt('Write out the extracted strings to a .po file instead of stdout'),
    ],
    'aliases' => ['pet'],
  ];

  // @todo add description, arguments, options & required-arguments
  $items['potion-extract-module'] = [
    'description' => dt('Extract'),
    'options' => [
      'file' => dt('Write out the extracted strings to a .po file instead of stdout'),
    ],
    'aliases' => ['pem'],
  ];

  // @todo add description, arguments, options & required-arguments
  $items['potion-extract-yaml'] = [
    'description' => dt('Extract'),
    'options' => [
      'file' => dt('Write out the extracted strings to a .po file instead of stdout'),
    ],
    'aliases' => ['pey'],
  ];
  return $items;
}

/**
 * Extract translation strings from templates files .twig.
 */
function drush_potion_extract_template() {
  /** @var \Drupal\potion\ExtractorInterface $extractor */
  // $extractor = \Drupal::service('potion.extract.template');.
  // @todo extract.
  // $strings = $extractor->;.
  // @todo stdout.
  // if ($file = drush_get_option('file')) {
  // file_put_contents($file, $export);
  // }
  // else {
  // drush_print($export);
  // }
}
