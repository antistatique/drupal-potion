<?php

/**
 * @file
 * Provides an included files to test translations extraction.
 */

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Implements hook_mail_alter().
 */
function potion_test_mail_alter(&$message) {
  new TranslatableMarkup('php.context', [], ['context' => 'Lolspeak']);

  t('php.baz');
  new TranslatableMarkup('php.foo', [], []);
  new TranslatableMarkup('php.foo.bar', [], []);
}
