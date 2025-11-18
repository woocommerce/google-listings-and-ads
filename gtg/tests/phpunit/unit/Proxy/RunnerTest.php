<?php

/**
 * RunnerTest
 *
 * @package   Google\GoogleTagGatewayLibrary\Tests\Proxy\Runner
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Tests\Proxy;

use Google\GoogleTagGatewayLibrary\Http\RequestHelper;
use Google\GoogleTagGatewayLibrary\Proxy\Measurement;
use Google\GoogleTagGatewayLibrary\Proxy\Runner;
use Google\GoogleTagGatewayLibrary\Tests\TestCase;
use Brain\Monkey\Functions;
use Google\GoogleTagGatewayLibrary\Http\ServerRequestContext;

final class RunnerTest extends TestCase
{
    /**
     * RequestHelper injected.
     *
     * @var \PHPUnit\Framework\MockObject\MockObject|RequestHelper
     */
    private RequestHelper $helperMock;

    /**
     * Measument class injected.
     *
     * @var Measurement
     */
    private Measurement $measurement;

    /**
     * Runner class to test.
     *
     * @var Runner
     */
    private Runner $runner;

    protected function setUp(): void
    {
        $context = new ServerRequestContext(
            ['SCRIPT_NAME' => '/src/measurement.php'],
            ['id' => 'G-12345'],
            'body',
        );

        $this->helperMock =
            $this->getMockBuilder(RequestHelper::class)
                 ->onlyMethods([
                     'isCurlInstalled',
                     'sendCurlRequest',
                     'setHeaders',
                 ])
                 ->getMock();

        $this->helperMock
             ->method('isCurlInstalled')
             ->willReturn(true);

        $this->measurement = new Measurement($this->helperMock, $context);
        $this->runner = new Runner($this->helperMock, $this->measurement);
    }

    public function testSendsSuccessResponseBack()
    {
        $this->helperMock
             ->method('sendCurlRequest')
             ->willReturn([
                 'statusCode' => 200,
                 'body' => 'ok',
                 'headers' => ['X-Test-Header: true'],
             ]);

        Functions\expect('http_response_code')->once()->with(200);
        $this->expectOutputString('ok');
        $this->helperMock
             ->expects($this->once())
             ->method('setHeaders')
             ->with(['X-Test-Header: true']);

        $this->runner->run();
    }

    public function testSendsClientErrorResponseBack()
    {
        $this->helperMock
             ->method('sendCurlRequest')
             ->willReturn([
                 'statusCode' => 500,
                 'body' => '',
                 'headers' => [],
             ]);

        Functions\expect('http_response_code')->once()->with(500);
        $this->expectOutputString('');
        $this->helperMock
             ->expects($this->once())
             ->method('setHeaders')
             ->with([]);

        $this->runner->run();
    }
}
