<?php

namespace Drupal\potion;

use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Component\Gettext\PoItem;

/**
 * Catalogue of unique messages.
 */
class MessageCatalogue {

  /**
   * Collection of PoItem.
   *
   * @var \Drupal\Component\Gettext\PoItem[]
   */
  private $messages = [];

  /**
   * Reset the messages catalogue.
   */
  public function reset() {
    $this->messages = [];
  }

  /**
   * Store the parsed values as a PoItem object in the $messages.
   *
   * @param string|array $msgid
   *   The translations source string or array of strings if it has plurals.
   * @param string $msgctxt
   *   The context this translation belongs to.
   * @param string $msgstr
   *   The translation. May be a string or array of strings if it has plurals.
   */
  public function add($msgid, $msgctxt = NULL, $msgstr = []) {
    // Save source & translations as string or array of strings if it's plural.
    $source      = is_array($msgid) ? implode(PluralTranslatableMarkup::DELIMITER, $msgid) : trim($msgid);
    $translation = is_array($msgid) ? implode(PluralTranslatableMarkup::DELIMITER, ['', '']) : '';

    if (!empty($msgstr)) {
      $translation = is_array($msgstr) ? implode(PluralTranslatableMarkup::DELIMITER, $msgstr) : $msgstr;
    }

    $item = new PoItem();
    $item->setFromArray([
      'context'     => $msgctxt,
      'source'      => $source,
      'translation' => $translation,
      'comment'     => NULL,
    ]);

    // Generate a uniq key by translations to avoid duplicates.
    $id = md5($source . $msgctxt);

    // Save the message.
    $this->messages[$id] = $item;
    return [$id => $item];
  }

  /**
   * Gets the messages.
   *
   * @return \Drupal\Component\Gettext\PoItem[]
   *   An array of messages.
   */
  public function all() {
    return $this->messages;
  }

  /**
   * Merges messages from the given Catalogue into the current one.
   *
   * @param self $catalogue
   *   The catalogue messages to merge into the current one.
   */
  public function merge(self $catalogue) {
    $this->messages = array_merge($this->messages, $catalogue->all());
  }

  /**
   * Count the number of messages in the catalogue.
   *
   * Be aware that pluralized messages count as 1.
   *
   * @return int
   *   The total of unique messages in the catalogue.
   */
  public function count() {
    return count($this->messages);
  }

}
