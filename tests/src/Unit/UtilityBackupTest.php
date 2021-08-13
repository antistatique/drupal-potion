<?php

namespace Drupal\Tests\potion\Unit;

/**
 * @coversDefaultClass \Drupal\potion\Utility
 * @group potion
 * @group potion_unit
 * @group potion_unit_utility
 */
class UtilityBackupTest extends UtilityTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $dir = '/var/tmp/';
    touch($dir . 'fr.po');
    touch($dir . 'de.po');
    touch($dir . 'de.po.~1~');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $dir = '/var/tmp/';
    @unlink($dir . 'fr.po');
    @unlink($dir . 'fr.po.~1~');
    @unlink($dir . 'de.po');
    @unlink($dir . 'de.po.~1~');
    @unlink($dir . 'de.po.~2~');
  }

  /**
   * @covers \Drupal\potion\Utility::backup
   */
  public function testBackup() {
    $dir      = '/var/tmp/';
    $filepath = $dir . 'fr.po';
    $backup   = $dir . 'fr.po.~1~';

    $result = $this->utility->backup($filepath);
    $this->assertIsString($result);

    $this->assertFileExists($filepath);
    $this->assertFileExists($backup);
    $this->assertFileExists($result);
    $this->assertEquals($backup, $result);
  }

  /**
   * @covers \Drupal\potion\Utility::backup
   */
  public function testBackupIncremental() {
    $dir           = '/var/tmp/';
    $filepath      = $dir . 'de.po';
    $should_backup = $dir . 'de.po.~2~';

    $backup = $this->utility->backup($filepath);

    $this->assertIsString($backup);
    $this->assertStringContainsString($backup, $should_backup);
  }

  /**
   * @covers \Drupal\potion\Utility::backup
   */
  public function testBackupInvalidFile() {
    $backup = $this->utility->backup('/fr.po');
    $this->assertFalse($backup);
  }

}
