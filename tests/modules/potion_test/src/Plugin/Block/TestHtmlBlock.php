<?php

namespace Drupal\potion_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block to test translations extraction.
 *
 * @Block(
 *   id = "test_html",
 *   admin_label = @Translation("php.annotation")
 *   label_count = @PluralTranslation(
 *     singular = "@count html block",
 *     plural = "@count html blocks",
 *   ),
 * )
 *
 * @see Drupal\block_test\Plugin\Block\TestHtmlBlock
 */
class TestHtmlBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Test translatable markup.
    new TranslatableMarkup('Hello sunshine');

    // Test translatable markup with tokens.
    new TranslatableMarkup('Hello sunshine @bar', ['@bar' => 'bar']);
    new TranslatableMarkup('Hello sunshine :bar', [':bar' => 'bar']);
    new TranslatableMarkup('Hello sunshine @bar :baz', ['@bar' => 'bar', 'baz' => 'baz']);

    // Test translatable markup with context.
    new TranslatableMarkup('php.context', [], ['context' => 'Lolspeak']);

    // Test translatable markup with context & others options.
    new TranslatableMarkup('php.context.ru', [], ['langcode' => 'ru', 'context' => 'Lolspeak']);

    // Test translatable t.
    $this->t('Hello dawn');
    t('Hello dawny');

    // Test duplicate t.
    $this->t('Hello dawny');

    // Test t with token.
    $this->t('Hello dawn @bar', ['@bar' => 'bar']);
    t('Hello dawny @bar', ['@bar' => 'bar']);

    // Test t with context.
    $this->t('Hello dawn @bar', ['@bar' => 'bar'], ['context' => 'Lolspeak']);
    t('Hello dawny @bar', ['@bar' => 'bar'], ['context' => 'Lolspeak']);

    // Test plural.
    new PluralTranslatableMarkup(1, 'singular @count', 'plural @count');

    // Test plural with token.
    new PluralTranslatableMarkup(1, 'singular @count @foo', 'plural @count @foo', ['@foo' => 'foo']);

    // Test plural with context.
    new PluralTranslatableMarkup(1, 'singular @count', 'plural @count', [], ['context' => 'Lolspeak']);

    // Test duplicate plural.
    new PluralTranslatableMarkup(9, 'singular @count', 'plural @count');

    // Test formatPlural.
    $this->formatPlural(1, '1 byte', '@count bytes');

    // Test formatPlural with context.
    $this->formatPlural(1, '1 byte', '@count bytes', [], ['context' => 'Lolspeak']);

    // Test translate.
    $this->translate('Hello moonlight');

    // Test translate.
    $this->translate('Hello moonlight', [], ['langcode' => 'ru']);

    // Test translate. with token.
    $this->translate('Hello moonlight @foobar', ['@foobar' => 'foobar']);

    // Test translate. with token & context.
    $this->translate('Hello moonlight @foobar', ['@foobar' => 'foobar'], ['context' => 'Lolspeak']);

    // dt()
    return ['#markup' => 'Hello world!'];
  }

}
