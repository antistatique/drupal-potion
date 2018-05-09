<?php

namespace Drupal\potion\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Potion - Gettext configuration form.
 */
class GettextSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['potion.gettext.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'potion_gettext_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('potion.gettext.settings');

    $form['gettext_information'] = [
      '#type'  => 'details',
      '#title' => $this->t('Gettext utilities details'),
      '#open'  => TRUE,
    ];

    $form['gettext_information']['path'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Path to gettext binaries files'),
      '#description'   => $this->t('Enter the full path to <code>gettext</code> executable files. Example: "/var/gettext/bin". This may be overriden in settings.php'),
      '#required'      => FALSE,
      '#default_value' => $config->get('path'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Allow the value to be empty.
    if (empty($form_state->getValue('path'))) {
      return;
    }

    // Get the path from user input & add trailing director separator.
    $path = rtrim($form_state->getValue('path'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    // Collection of utilities that must be executable in the given $path.
    $utilities = [
      'autopoint',
      'gettext',
      'gettextize',
      'msgcat',
      'msgcomm',
      'msgen',
      'msgfilter',
      'msggrep',
      'msgmerge',
      'msguniq',
      'recode-sr-latin',
      'envsubst',
      'msgattrib',
      'msgcmp',
      'msgconv',
      'msgexec',
      'msgfmt',
      'msginit',
      'msgunfmt',
      'ngettext',
      'xgettext',
    ];

    if (!is_dir($path)) {
      $form_state->setErrorByName('path', $this->t("The directory %directory does not exist.", ['%directory' => $path]));
    }
    else {
      foreach ($utilities as $utility) {
        if (!is_file($path . $utility)) {
          $form_state->setErrorByName('path', $this->t("The utility %utility does not exist.", ['%utility' => $path . $utility]));
        }
        if (!is_executable($path . $utility)) {
          $form_state->setErrorByName('path', $this->t("The utility %utility is not executable.", ['%utility' => $path . $utility]));
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $path = '';
    if (!empty($form_state->getValue('path'))) {
      // Get the path from user input & add trailing director separator.
      $path = rtrim($form_state->getValue('path'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    $this->config('potion.gettext.settings')
      ->set('path', $path)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
