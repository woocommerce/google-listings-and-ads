<?php

/**
 * SettingsTest
 *
 * @package   Google\GoogleTagGatewayLibrary\Tests\Wordpress\Settings
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Tests\Wordpress;

use Brain\Monkey\Functions;
use Google\GoogleTagGatewayLibrary\Tests\WordpressTestCase;
use Google\GoogleTagGatewayLibrary\Wordpress\Logger;
use Google\GoogleTagGatewayLibrary\Wordpress\Settings;
use PHPUnit\Framework\MockObject\MockObject;

final class SettingsTest extends WordpressTestCase
{
    /**
     * Settings object to test.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Logger instance injected.
     *
     * @var Logger&MockObject
     */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(Logger::class);
        $this->settings = new Settings($this->logger);
    }

    public function testCanUseModRewriteIsTrue()
    {
        Functions\expect('apache_mod_loaded')
            ->with('mod_rewrite', false)
            ->andReturn(true);
        Functions\expect('get_option')
            ->with('permalink_structure')
            ->andReturn('/%year%/%monthnum%/%day%/%postname%/');

        $result = $this->settings->canUseModRewrite();
        $this->assertTrue($result);
    }

    public function testCanUseModRewriteIsTrueFromSavedValue()
    {
        Functions\expect('apache_mod_loaded')
            ->once()
            ->with('mod_rewrite', false)
            ->andReturn(true);
        Functions\expect('get_option')
            ->once()
            ->with('permalink_structure')
            ->andReturn('/%year%/%monthnum%/%day%/%postname%/');

        $result1 = $this->settings->canUseModRewrite();
        $result2 = $this->settings->canUseModRewrite();

        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    public function testCanUseModRewriteIsFalse()
    {
        Functions\expect('apache_mod_loaded')
            ->with('mod_rewrite', false)
            ->andReturn(false);
        Functions\expect('get_option')
            ->with('permalink_structure')
            ->andReturn('/%year%/%monthnum%/%day%/%postname%/');

        $result = $this->settings->canUseModRewrite();
        $this->assertFalse($result);
    }

    public static function permalinksDisabled()
    {
        return [
            [''],
            [false],
            [null],
        ];
    }

    /*
     * @dataProvider permalinksDisabled
     */
    public function testCanUseModRewriteIsFalseWithPermalinksDisabled(
        $permalinkValue = null
    ) {
        Functions\expect('apache_mod_loaded')
            ->with('mod_rewrite', false)
            ->andReturn(true);
        Functions\expect('get_option')
            ->with('permalink_structure')
            ->andReturn($permalinkValue);

        $result = $this->settings->canUseModRewrite();
        $this->assertFalse($result);
    }

    /**
     * @dataProvider permalinksDisabled
     */
    public function testCanUseModRewriteIsFalseFromSavedPermalinkValueOf(
        $permalinks = null
    ) {
        Functions\expect('apache_mod_loaded')
            ->once()
            ->with('mod_rewrite', false)
            ->andReturn(true);
        Functions\expect('get_option')
            ->once()
            ->with('permalink_structure')
            ->andReturn($permalinks);

        $result1 = $this->settings->canUseModRewrite();
        $result2 = $this->settings->canUseModRewrite();

        $this->assertFalse($result1);
        $this->assertFalse($result2);
    }

    public function testIsRewritingEnabledIsTrueWithModRewriteAndPermalinks()
    {
        Functions\expect('apache_mod_loaded')
            ->with('mod_rewrite', false)
            ->andReturn(true);
        Functions\expect('get_option')
            ->with('permalink_structure')
            ->andReturn('/%year%/%monthnum%/%day%/%postname%/');

        $result = $this->settings->isRewritingEnabled();
        $this->assertTrue($result);
    }

    public static function rewriteDisabled()
    {
        return [
            [/*mod_rewrite=*/ true, /*permalinks=*/ false],
            [/*mod_rewrite=*/ false, /*permalinks=*/ false],
            [/*mod_rewrite=*/ true, /*permalinks=*/ ''],
            [/*mod_rewrite=*/ false, /*permalinks=*/ ''],
            [/*mod_rewrite=*/ true, /*permalinks=*/ null],
            [/*mod_rewrite=*/ false, /*permalinks=*/ null],
            [
                /*mod_rewrite=*/ false,
                /*permalinks=*/ '/%year%/%monthnum%/%day%/%postname%/',
            ],
        ];
    }

    /**
     * @dataProvider rewriteDisabled
     */
    public function testIsRewritingEnabledIsFalseWith(
        $modRewrite,
        $permalinks = null
    ) {
        Functions\expect('apache_mod_loaded')
            ->with('mod_rewrite', false)
            ->andReturn($modRewrite);
        Functions\expect('get_option')
            ->with('permalink_structure')
            ->andReturn($permalinks);

        $result = $this->settings->isRewritingEnabled();
        $this->assertFalse($result);
    }

    public function testGetTagIdReturnsValueFromGetOption()
    {
        Functions\expect('get_option')
            ->with('googletaggateway_tag_id', '')
            ->andReturn('G-TEST');

        $result = $this->settings->getTagId();
        $this->assertEquals('G-TEST', $result);
    }

    public function testGetTagIdReturnsValueFromSavedValue()
    {
        Functions\expect('get_option')
            ->once()
            ->with('googletaggateway_tag_id', '')
            ->andReturn('G-12345');

        $result1 = $this->settings->getTagId();
        $result2 = $this->settings->getTagId();

        $this->assertEquals('G-12345', $result1);
        $this->assertEquals('G-12345', $result2);
    }

    public function testGetTagIdReturnsValueFromSavedValueWhileEmpty()
    {
        Functions\expect('get_option')
            ->once()
            ->with('googletaggateway_tag_id', '')
            ->andReturn('');

        $result1 = $this->settings->getTagId();
        $result2 = $this->settings->getTagId();

        $this->assertEquals('', $result1);
        $this->assertEquals('', $result2);
    }

    public function testGetMeasurementPathReturnsValueFromGetOption()
    {
        Functions\expect('get_option')
            ->with('googletaggateway_measurement_path', '')
            ->andReturn('custom/measurement');

        $result = $this->settings->getMeasurementPath();
        $this->assertEquals('custom/measurement', $result);
    }

    public function testGetMeasurementPathReturnsValueFromSavedValue()
    {
        Functions\expect('get_option')
            ->once()
            ->with('googletaggateway_measurement_path', '')
            ->andReturn('path/to/measurement');

        $result1 = $this->settings->getMeasurementPath();
        $result2 = $this->settings->getMeasurementPath();

        $this->assertEquals('path/to/measurement', $result1);
        $this->assertEquals('path/to/measurement', $result2);
    }

    public function testGetMeasurementPathReturnsValueFromSavedValueWhileEmpty()
    {
        Functions\expect('get_option')
            ->once()
            ->with('googletaggateway_measurement_path', '')
            ->andReturn('');

        $result1 = $this->settings->getMeasurementPath();
        $result2 = $this->settings->getMeasurementPath();

        $this->assertEquals('', $result1);
        $this->assertEquals('', $result2);
    }

    public function testUpdateTagIdPassThroughSavedFailure()
    {
        Functions\expect('update_option')
            ->with('googletaggateway_tag_id', 'G-TEST')
            ->andReturn(false);

        $result = $this->settings->updateTagId('G-TEST');

        $this->assertFalse($result);
    }

    public function testUpdateTagIdFailsWithInvalidValue()
    {
        Functions\expect('update_option')->never();

        $result = $this->settings->updateTagId('invalid^Tag!ID');

        $this->assertFalse($result);
    }

    public function testUpdateTagIdLogsErrorWithInvalidValue()
    {
        $this->logger
             ->expects($this->once())
             ->method('log')
             ->with("Invalid tag ID attempted to be stored: invalid^Tag!ID");

        $result = $this->settings->updateTagId('invalid^Tag!ID');

        $this->assertFalse($result);
    }

    public function testUpdateTagIdPassThroughSavedSuccess()
    {
        Functions\expect('update_option')
            ->with('googletaggateway_tag_id', 'G-TEST')
            ->andReturn(true);

        $result = $this->settings->updateTagId('G-TEST');

        $this->assertTrue($result);
    }

    public function testUpdateTagIdUpdatesSavedValueOnSuccess()
    {
        Functions\expect('update_option')
            ->with('googletaggateway_tag_id', 'G-UPDATE')
            ->andReturn(true);

        Functions\expect('get_option')->never();

        $this->settings->updateTagId('G-UPDATE');
        $result = $this->settings->getTagId();

        $this->assertEquals('G-UPDATE', $result);
    }

    public function testUpdateTagIdDoesNotUpdateSavedValueOnFailure()
    {
        Functions\expect('update_option')
            ->with('googletaggateway_tag_id', 'G-FAILED')
            ->andReturn(false);

        Functions\expect('get_option')
            ->once()
            ->with('googletaggateway_tag_id', '')
            ->andReturn('G-GET');

        $this->settings->updateTagId('G-FAILED');
        $result = $this->settings->getTagId();

        $this->assertEquals('G-GET', $result);
    }

    public function testUpdateTagIdDoesNotUpdateWithPriorValueSet()
    {
        Functions\expect('update_option')
            ->once()
            ->with('googletaggateway_tag_id', 'G-PRIOR')
            ->andReturn(true);

        Functions\expect('update_option')
            ->once()
            ->with('googletaggateway_tag_id', 'G-FAILED')
            ->andReturn(false);

        Functions\expect('get_option')->never();

        $this->settings->updateTagId('G-PRIOR');
        $this->settings->updateTagId('G-FAILED');
        $result = $this->settings->getTagId();

        $this->assertEquals('G-PRIOR', $result);
    }

    public function testUpdateMeasurementPathPassThroughSavedFailure()
    {
        Functions\expect('update_option')
            ->with('googletaggateway_measurement_path', 'failed/mpath')
            ->andReturn(false);

        $result = $this->settings->updateMeasurementPath('failed/mpath');

        $this->assertFalse($result);
    }

    public function testUpdateMeasurementPathFailsWithInvalidValue()
    {
        Functions\expect('update_option')->never();

        $result = $this->settings->updateMeasurementPath('cats///');

        $this->assertFalse($result);
    }

    public function testUpdateMeasurementPathLogsErrorWithInvalidValue()
    {
        $this->logger
             ->expects($this->once())
             ->method('log')
             ->with("Invalid measurement path attempted to be stored: cats///");

        $result = $this->settings->updateMeasurementPath('cats///');

        $this->assertFalse($result);
    }

    public function testUpdateMeasurementPathPassThroughSavedSuccess()
    {
        Functions\expect('update_option')
            ->with('googletaggateway_measurement_path', 'saved/mpath')
            ->andReturn(true);

        $result = $this->settings->updateMeasurementPath('saved/mpath');

        $this->assertTrue($result);
    }

    public function testUpdateMeasurementPathUpdatesSavedValueOnSuccess()
    {
        Functions\expect('update_option')
            ->with('googletaggateway_measurement_path', 'updated/mpath')
            ->andReturn(true);

        Functions\expect('get_option')->never();

        $this->settings->updateMeasurementPath('updated/mpath');
        $result = $this->settings->getMeasurementPath();

        $this->assertEquals('updated/mpath', $result);
    }

    public function testUpdateMeasurementPathDoesNotUpdateSavedValueOnFailure()
    {
        Functions\expect('update_option')
            ->with('googletaggateway_measurement_path', 'failed/mpath')
            ->andReturn(false);

        Functions\expect('get_option')
            ->once()
            ->with('googletaggateway_measurement_path', '')
            ->andReturn('get/mpath');

        $this->settings->updateMeasurementPath('failed/mpath');
        $result = $this->settings->getMeasurementPath();

        $this->assertEquals('get/mpath', $result);
    }

    public function testUpdateMeasurementPathDoesNotUpdateWithPriorValueSet()
    {
        Functions\expect('update_option')
            ->once()
            ->with('googletaggateway_measurement_path', 'prior/mpath')
            ->andReturn(true);

        Functions\expect('update_option')
            ->once()
            ->with('googletaggateway_measurement_path', 'failed/mpath')
            ->andReturn(false);

        Functions\expect('get_option')->never();

        $this->settings->updateMeasurementPath('prior/mpath');
        $this->settings->updateMeasurementPath('failed/mpath');
        $result = $this->settings->getMeasurementPath();

        $this->assertEquals('prior/mpath', $result);
    }
}
