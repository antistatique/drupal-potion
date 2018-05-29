<?php

namespace Drupal\potion_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block to test translations extraction.
 *
 * @Block(
 *   id = "test_annotation",
 *   label = @Translation("php.annotation", context = "Lolspeak")
 *   admin_label = @Translation("php.annotation.admin_label !title", arguments = {"!title" = "Foo"})
 *   subtitle = @Translation("php.annotation.subtitle",  context = "Lolspeak")
 *   label_count = @PluralTranslation(
 *     singular = "@count html block",
 *     plural = "@count html blocks",
 *    context = "Lolspeak",
 *   ),
 *   label_count = @PluralTranslation(
 *     singular = "@count php.annotation",
 *     plural = "@count php.annotations",
 *   ),
 * )
 *
 * @see Drupal\block_test\Plugin\Block\TestHtmlBlock
 */
class TestAnnotationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => 'Hello moon!'];
  }

}
