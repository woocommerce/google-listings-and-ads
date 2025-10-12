<?php

/**
 * SnippetHandlerTest
 *
 * @package   Google\GoogleTagGatewayLibrary\Tests\Wordpress\SnippetHandler
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Tests\Wordpress;

use Brain\Monkey\Functions;
use Google\GoogleTagGatewayLibrary\GoogleTagGatewayHelper;
use Google\GoogleTagGatewayLibrary\Tests\WordpressTestCase;
use Google\GoogleTagGatewayLibrary\Wordpress\Logger;
use Google\GoogleTagGatewayLibrary\Wordpress\SnippetHandler;
use PHPUnit\Framework\MockObject\MockObject;

final class SnippetHandlerTest extends WordpressTestCase
{
    /**
     * The SnippetHandler instance to test.
     *
     * @var SnippetHandler
     */
    private $snippetHandler;

    /**
     * The GoogleTagGatewayHelper injected into the SnippetHandler test
     * instance.
     *
     * @var GoogleTagGatewayHelper
     */
    private $helper;

    /**
     * The Logger injected into the SnippetHandler test instance.
     *
     * @var Logger&MockObject
     */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->helper = new GoogleTagGatewayHelper('G-TEST', [
            'mpath' => '/test/mpath',
        ]);
        $this->logger = $this->createMock(Logger::class);

        $this->snippetHandler = new SnippetHandler(
            $this->helper,
            $this->logger,
        );
    }

    public function testInjectsAll3SnippetsOntoThePage(): void
    {
        Functions\expect('wp_print_inline_script_tag')->times(3);
        $this->snippetHandler->inject();
    }

    public function testInjectsTopScriptOntoThePage(): void
    {
        $resources = $this->helper->createResources();
        Functions\expect('wp_print_inline_script_tag')
            ->once()
            ->with($resources['topScript']);

        Functions\expect('wp_print_inline_script_tag')->times(2);

        $this->snippetHandler->inject();
    }

    public function testInjectsSrcTagOntoThePage(): void
    {
        $resources = $this->helper->createResources();
        Functions\expect('wp_print_inline_script_tag')
            ->once()
            ->with('', ['src' => $resources['src'], 'async' => true]);

        Functions\expect('wp_print_inline_script_tag')->times(2);

        $this->snippetHandler->inject();
    }

    public function testInjectsMainScriptTagOntoThePage(): void
    {
        $resources = $this->helper->createResources();
        Functions\expect('wp_print_inline_script_tag')
            ->once()
            ->with($resources['script']);

        Functions\expect('wp_print_inline_script_tag')->times(2);

        $this->snippetHandler->inject();
    }

    public function testDoesNotInjectWhenErrorWouldOccur(): void
    {
        $badHelper = new GoogleTagGatewayHelper('invalid!TAG*iD');
        $snippet = new SnippetHandler($badHelper, $this->logger);

        Functions\expect('wp_print_inline_script_tag')->never();

        $snippet->inject();
    }

    public function testSendsLogMessageWhenErrorWouldOccur(): void
    {
        $badHelper = new GoogleTagGatewayHelper('invalid!TAG*iD');
        $snippet = new SnippetHandler($badHelper, $this->logger);

        $this->logger
             ->expects($this->once())
             ->method('log')
             ->with(
                 $this->stringStartsWith(
                     "An unexpected error occurred while injecting " .
                     "GTG snippet:",
                 )
             );

        $snippet->inject();
    }
}
