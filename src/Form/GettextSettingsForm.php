<?php

namespace Drupal\potion\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\potion\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Potion - Gettext configuration form.
 */
class GettextSettingsForm extends ConfigFormBase {

  /**
   * The Utility service of Potion.
   *
   * @var \Drupal\potion\Utility
   */
  protected $utility;

  /**
   * GettextSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\potion\Utility $utility
   *   Utility methods for Potion.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Utility $utility) {
    parent::__construct($config_factory);
    $this->utility = $utility;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('potion.utility')
    );
  }

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
      '#description'   => $this->t('Enter the full path to <code>gettext</code> executable files. Example: "/var/gettext/bin". This may be overridden in settings.php'),
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
    $path = $this->utility->sanitizePath($form_state->getValue('path'));

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
      $path = $this->utility->sanitizePath($form_state->getValue('path'));
    }

    $this->config('potion.gettext.settings')
      ->set('path', $path)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
