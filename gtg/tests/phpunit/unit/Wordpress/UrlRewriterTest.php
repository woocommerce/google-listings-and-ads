<?php

/**
 * UrlRewriterTest
 *
 * @package   Google\GoogleTagGatewayLibrary\Tests\Wordpress\UrlRewriter
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Tests\Wordpress;

use Brain\Monkey\Functions;
use Google\GoogleTagGatewayLibrary\Core\Context;
use Google\GoogleTagGatewayLibrary\Tests\WordpressTestCase;
use Google\GoogleTagGatewayLibrary\Wordpress\Settings;
use Google\GoogleTagGatewayLibrary\Wordpress\UrlRewriter;
use PHPUnit\Framework\MockObject\MockObject;

final class UrlRewriterTest extends WordpressTestCase
{
    /**
     * UrlRewriter object to test.
     *
     * @var UrlRewriter
     */
    private $urlRewriter;

    /**
     * Settings injected into UrlRewriter
     *
     * @var Settings&MockObject
     */
    private $settings;

    /**
     * Context injected into UrlRewriter
     *
     * @var Context
     */
    private $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = new Context(
            '/root/lib', /* libraryRoot */
            '/root', /* documentRoot */
        );
        $this->settings = $this->createMock(Settings::class);
        $this->urlRewriter = new UrlRewriter($this->context, $this->settings);
    }

    public function testAddModRulesReturnsFalseWhenModRewriteDisabled(): void
    {
        $this->settings->method('canUseModRewrite')->willReturn(false);
        $result = $this->urlRewriter->addModRewriteRules();
        $this->assertFalse($result);
    }

    public function testAddModRulesReturnsFalseWhenUrlPathCannotBeDetermined(): void
    {
        $this->settings->method('canUseModRewrite')->willReturn(true);
        $badContext = new Context(
            '/root/lib', /* libraryRoot */
            '/bad', /* documentRoot */
        );
        $urlRewriter = new UrlRewriter($badContext, $this->settings);

        $result = $urlRewriter->addModRewriteRules();
        $this->assertFalse($result);
    }

    public function testAddModRulesReturnsTrueOnSuccess(): void
    {
        $this->settings->method('canUseModRewrite')->willReturn(true);
        $this->settings->method('getMeasurementPath')->willReturn('mpath');
        Functions\expect('add_rewrite_rule')->once();

        $result = $this->urlRewriter->addModRewriteRules();
        $this->assertTrue($result);
    }

    public static function mpathsToTest(): array
    {
        return [
            ['path/to/measurement'],
            ['/path/to/measurement'],
            ['/path/to/measurement/'],
            ['path/to/measurement/']
        ];
    }

    /**
     * @dataProvider mpathsToTest
     */
    public function testAddModRulesSetsRulesCorrectly(string $mpath): void
    {
        $this->settings->method('canUseModRewrite')->willReturn(true);
        $this->settings->method('getTagId')->willReturn('G-MODREWRITE');
        $this->settings->method('getMeasurementPath')->willReturn($mpath);

        Functions\expect('add_rewrite_rule')
            ->once()
            ->with(
                '^(path\/to\/measurement)(\/?.*)',
                '/lib/dist/measurement.php?id=G-MODREWRITE&mpath=$1&s=$2',
                'top'
            );

        $this->urlRewriter->addModRewriteRules();
    }
}
