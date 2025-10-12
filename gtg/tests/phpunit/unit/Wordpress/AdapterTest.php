<?php

/**
 * AdapterTest
 *
 * @package   Google\GoogleTagGatewayLibrary\Tests\Wordpress\Adapter
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Tests\Wordpress;

use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Google\GoogleTagGatewayLibrary\GoogleTagGatewayHelper;
use Google\GoogleTagGatewayLibrary\Tests\WordpressTestCase;
use Google\GoogleTagGatewayLibrary\Wordpress\Adapter;
use Google\GoogleTagGatewayLibrary\Wordpress\Logger;
use Google\GoogleTagGatewayLibrary\Wordpress\Settings;
use Google\GoogleTagGatewayLibrary\Wordpress\SnippetHandler;
use Google\GoogleTagGatewayLibrary\Wordpress\UrlRewriter;
use PHPUnit\Framework\MockObject\MockObject;

final class AdapterTest extends WordpressTestCase
{
    /**
     * The Adapter instance to test.
     *
     * @var Adapter
     */
    private $adapter;

    /**
     * GoogleTagGatewayHelper injected.
     *
     * @var GoogleTagGatewayHelper
     */
    private $helper;

    /**
     * Logger instance injected.
     *
     * @var Logger&MockObject
     */
    private $logger;

    /**
     * Settings instance injected.
     *
     * @var Settings&MockObject
     */
    private $settings;

    /**
     * SnippetHandler instance injected.
     *
     * @var SnippetHandler&MockObject
     */
    private $snippetHandler;

    /**
     * UrlRewriter instance injected.
     *
     * @var UrlRewriter&MockObject
     */
    private $urlRewriter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->helper = new GoogleTagGatewayHelper('G-TEST', ['mpath' => '/m/']);
        $this->logger = $this->createMock(Logger::class);
        $this->settings = $this->createMock(Settings::class);
        $this->snippetHandler = $this->createMock(SnippetHandler::class);
        $this->urlRewriter = $this->createMock(UrlRewriter::class);

        $this->adapter = new Adapter(
            $this->helper,
            $this->logger,
            $this->settings,
            $this->snippetHandler,
            $this->urlRewriter,
        );
    }

    public function testAdapterInitializeLogsIfTagIdIsMissing(): void
    {
        $this->settings->method('getTagId')->willReturn('');

        $this->logger
             ->expects($this->once())
             ->method('log')
             ->with('GoogleTagGateway Tag ID must be set when using GTG.');

        $this->adapter->initialize();
    }

    public function testAdapterInitializeRegistersNoActionsIfTagIdIsMissing(): void
    {
        $this->settings->method('getTagId')->willReturn('');

        Functions\expect('add_action')->never();

        $this->adapter->initialize();
    }

    public function testAdapterInitializeDoesNotRegisterRewriteRulesWithMissingMpath(): void
    {
        args:
        $this->settings->method('getMeasurementPath')->willReturn('');

        Actions\expectAdded('init')->never();
        Actions\expectAdded('template_redirect')->never();

        $this->adapter->initialize();
    }

    public function testAdapterInitializeRegistersScriptInjectionWhenMpathMissing(): void
    {
        $this->settings->method('getTagId')->willReturn('G-TEST');
        $this->settings->method('getMeasurementPath')->willReturn('');

        Actions\expectAdded('wp_head')
            ->with(\Mockery::type('callable'), 0)
            ->once();

        $this->adapter->initialize();
    }

    public function testAdapterInitializeLogsMessageWhenMpathMissing(): void
    {
        $this->settings->method('getTagId')->willReturn('G-TEST');
        $this->settings->method('getMeasurementPath')->willReturn('');

        $this->logger
             ->expects($this->once())
             ->method('log')
             ->with(
                 'GoogleTagGateway should use a measurement path to ensure ' .
                 'full functionality.'
             );

        $this->adapter->initialize();
    }

    public function testAdapterInitializeDoesntLogWithSuccessfullModRewrite(): void
    {
        $this->settings->method('getTagId')->willReturn('G-TEST');
        $this->settings->method('getMeasurementPath')->willReturn('/m/');
        $this->settings->method('canUseModRewrite')->willReturn(true);

        $this->logger
             ->expects($this->never())
             ->method('log');

        $this->adapter->initialize();
    }

    public function testAdapterInitializeSetsRewriteRulesForModRewrite(): void
    {
        $this->settings->method('getTagId')->willReturn('G-TEST');
        $this->settings->method('getMeasurementPath')->willReturn('/m/');
        $this->settings->method('canUseModRewrite')->willReturn(true);

        Actions\expectAdded('init')->with(\Mockery::type('callable'))->once();

        $this->adapter->initialize();
    }

    public function testAdapterInitializeLogsMessageWhenRulesAreDisabled(): void
    {
        $this->settings->method('getTagId')->willReturn('G-TEST');
        $this->settings->method('getMeasurementPath')->willReturn('/m/');
        $this->settings->method('canUseModRewrite')->willReturn(false);

        $this->logger
             ->expects($this->once())
             ->method('log')
             ->with(
                 'URL Rewriting on Wordpress server is disabled or not ' .
                 'supported. Please enable permalinks in the ' .
                 '`WP Admin > Settings > Permalinks` page and ensure that ' .
                 'mod_rewrite is supported on your server to ensure ' .
                 'Google Tag Gateway will function properly.',
             );

        $this->adapter->initialize();
    }

    public function testAdapterInitializeOnlyInjectsSnippetWhenRulesAreDisabled(): void
    {
        $this->settings->method('getTagId')->willReturn('G-TEST');
        $this->settings->method('getMeasurementPath')->willReturn('/m/');
        $this->settings->method('canUseModRewrite')->willReturn(false);

        Actions\expectAdded('init')->never();
        Actions\expectAdded('template_redirect')->never();
        Actions\expectAdded('wp_head')->once();

        $this->adapter->initialize();
    }

    public function testAdapterInitializeRemovesMpathFromSrcWhenRulesAreDisabled(): void
    {
        $this->settings->method('getTagId')->willReturn('G-TEST');
        $this->settings->method('getMeasurementPath')->willReturn('/m/');
        $this->settings->method('canUseModRewrite')->willReturn(false);

        $this->helper
             ->setDefaultPhpRedirectorFile('/test/dir/src/measurement.php');
        $_SERVER['DOCUMENT_ROOT'] = '/test/dir';

        $this->adapter->initialize();


        $resources = $this->helper->createResources();
        $this->assertEquals('/src/measurement.php?id=G-TEST', $resources['src']);
    }

    public static function updateOperations(): array
    {
        return [
            [['tagId' => 'G-NEW']],
            [['measurementPath' => '/new/path/']],
            [['tagId' => 'G-NEW', 'measurementPath' => '/new/path/']],
        ];
    }

    /**
     * @dataProvider updateOperations
     */
    public function testAdapterUpdateSetsModRewriteRulesOnChangesTo(
        array $values
    ): void {
        $this->settings
             ->method('updateTagId')
             ->willReturn(true);
        $this->settings
             ->method('updateMeasurementPath')
             ->willReturn(true);
        $this->urlRewriter->method('addModRewriteRules')->willReturn(true);

        Functions\expect('flush_rewrite_rules')->once();

        $this->adapter->update($values);
    }

    /**
     * @dataProvider updateOperations
     */
    public function testAdapterUpdateSkipsRulesOnFailedUpdatesTo(
        array $values
    ): void {
        $this->settings
             ->method('updateTagId')
             ->willReturn(false);
        $this->settings
             ->method('updateMeasurementPath')
             ->willReturn(false);
        $this->urlRewriter->method('addModRewriteRules')->willReturn(true);

        Functions\expect('flush_rewrite_rules')->never();

        $this->adapter->update($values);
    }

    /**
     * @dataProvider updateOperations
     */
    public function testAdapterUpdateSetsNoRulesOnFailedRuleAdding(
        array $values
    ): void {
        $this->settings
             ->method('updateTagId')
             ->willReturn(true);
        $this->settings
             ->method('updateMeasurementPath')
             ->willReturn(true);
        $this->urlRewriter->method('addModRewriteRules')->willReturn(false);

        Functions\expect('flush_rewrite_rules')->never();

        $this->adapter->update($values);
    }

    public function testAdapterUpdateOnlyCallsTagIdForTagIdChange(): void
    {
        $this->settings
             ->expects($this->once())
             ->method('updateTagId')
             ->with('G-NEW');
        $this->settings
             ->expects($this->never())
             ->method('updateMeasurementPath');
        $this->settings->method('getMeasurementPath')->willReturn('/default');

        $this->adapter->update(['tagId' => 'G-NEW']);
    }

    public function testAdapterUpdateOnlyCallsMeasurementPathChange(): void
    {
        $this->settings
             ->expects($this->never())
             ->method('updateTagId');
        $this->settings
             ->expects($this->once())
             ->method('updateMeasurementPath')
             ->with('/m/');
        $this->settings->method('getMeasurementPath')->willReturn('/m/');

        $this->adapter->update(['measurementPath' => '/m/']);
    }

    public function testAdapterUpdateCallsBothUpdatesOnChange(): void
    {
        $this->settings
             ->expects($this->once())
             ->method('updateTagId')
             ->with('G-NEW');
        $this->settings
             ->expects($this->once())
             ->method('updateMeasurementPath')
             ->with('/m/');
        $this->settings->method('getMeasurementPath')->willReturn('/m/');

        $this->adapter->update([
            'tagId' => 'G-NEW',
            'measurementPath' => '/m/',
        ]);
    }

    public function testAdapterUpdateGeneratesMeasurementPathWhenMissing(): void
    {
        $this->settings
             ->expects($this->once())
             ->method('updateMeasurementPath');

        $this->settings->method('getMeasurementPath')->willReturn('');

        $this->adapter->update([
            'tagId' => 'G-NEW',
        ]);
    }
}
