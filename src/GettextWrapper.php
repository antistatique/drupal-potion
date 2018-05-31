<?php

namespace Drupal\potion;

use Symfony\Component\Process\Process;
use Drupal\potion\Exception\GettextException;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Contains wrapper methods for the Gettext libraries.
 */
class GettextWrapper {
  /**
   * The gettext settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $gettextConfig;

  /**
   * Path to gettext binaries files.
   *
   * This can be changed in settings.php or in the Potion Configuration Form.
   * When empty, the $PATH values are used.
   *
   * @var string
   */
  protected $path;

  /**
   * Construct the GettextWrapper object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->gettextConfig = $config_factory->get('potion.gettext.settings');

    $this->path = '';
    // Get path from config & sanitize it with a trailing directory separator.
    if (!empty($this->gettextConfig->get('path'))) {
      $this->path = $this->gettextConfig->get('path');
    }
  }

  /**
   * Assert file integrity: format, header, domain.
   *
   * - format:  Asserts translations strings formatted according lang.
   * - header:  Asserts the header exists & is valid.
   * - domain:  Looking for conflicted strings.
   *
   * @param string $src
   *   The file to validate.
   *
   * @return bool
   *   TRUE if the given file is valid, FALSE otherwise.
   *
   * @throws \Drupal\potion\Exception\GettextException
   */
  public function msgfmt($src) {
    $cmd = $this->path . 'msgfmt';

    // When the path is forced (a.k.a not resolved by $PATH env)
    // asserts the command exists & is executable.
    if (!empty($this->path) && (!is_file($cmd) || !is_executable($cmd))) {
      throw GettextException::commandNotFound($cmd);
    }

    try {
      $process = new Process([$cmd, '-o', '/dev/null', '--check', $src]);
      $process->run();
    }
    catch (\Exception $e) {
      throw new GettextException($e->getMessage(), $e->getCode(), $e);
    }

    if ($process->getExitCode() > 0) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Concatenates and merges the specified PO files.
   *
   * It finds messages which are common to two or more of the specified PO
   * files.
   * Translations, comments, extracted comments, and file positions will
   * be cumulated.
   *
   * @param array $files
   *   The files to merges.
   * @param string $output
   *   The destination of merging file.
   *
   * @return bool
   *   TRUE if the given file is valid, FALSE otherwise.
   *
   * @throws \Drupal\potion\Exception\GettextException
   */
  public function msgcat(array $files, $output) {
    $cmd = $this->path . 'msgcat';

    // When the path is forced (a.k.a not resolved by $PATH env)
    // asserts the command exists & is executable.
    if (!empty($this->path) && (!is_file($cmd) || !is_executable($cmd))) {
      throw GettextException::commandNotFound($cmd);
    }

    // Generate the commande line.
    $processus = [$cmd];
    foreach ($files as $file) {
      $processus[] = $file;
    }
    $processus[] = '--output-file=' . $output;

    try {
      $process = new Process($processus);
      $process->run();
    }
    catch (\Exception $e) {
      throw new GettextException($e->getMessage(), $e->getCode(), $e);
    }

    if ($process->getExitCode() > 0) {
      return FALSE;
    }

    return TRUE;
  }

}
