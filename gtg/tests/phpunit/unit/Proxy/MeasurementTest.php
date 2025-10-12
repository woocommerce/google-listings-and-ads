<?php

/**
 * MeasurementTest
 *
 * @package   Google\GoogleTagGatewayLibrary\Tests\Proxy\Measurement
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Tests\Proxy;

use Google\GoogleTagGatewayLibrary\Http\RequestHelper;
use Google\GoogleTagGatewayLibrary\Http\ServerRequestContext;
use Google\GoogleTagGatewayLibrary\Proxy\Measurement;
use PHPUnit\Framework\TestCase;

final class MeasurementTest extends TestCase
{
    /**
     * Request helper mocked.
     *
     * @var \PHPUnit\Framework\MockObject\MockObject|RequestHelper
     */
    private $helperMock;

    /**
     * Measurement test instance.
     *
     * @var Measurement
     */
    private $measurement;

    protected function setUp(): void
    {
        $this->helperMock =
            $this->getMockBuilder(RequestHelper::class)
                 ->onlyMethods([
                     'invalidRequest',
                     'isCurlInstalled',
                     'sendCurlRequest',
                     'sendFileGetContents',
                 ])
                 ->getMock();
    }

    public static function requestBindings()
    {
        return [
          'using curl' => [
            /* requestCaller= */'sendCurlRequest',
            /* dontCall= */'sendFileGetContents',
          ],
          'using file_get_contents' => [
            /* requestCaller= */'sendFileGetContents',
            /* dontCall= */'sendCurlRequest',
          ],
        ];
    }

    /**
     * @dataProvider requestBindings
     */
    public function testRespondsWithAScriptRequestForwarded(
        $requestCaller,
        $dontCall
    ) {
        $this->createMeasurement(
            '/src/measurement.php?id=G-12345&s=/gtag/js?id=G-12345'
        );

        $responseBody = 'const path = "/PHP_GTG_REPLACE_PATH/";';
        $scriptHeaders = ['Content-Type: application/javascript'];
        $this->assertRequestSent(
            [
                'handler' => $requestCaller,
                 'url' => 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/gtag/js?id=G-12345',
            ],
            [
                 'body' => $responseBody,
                 'headers' => $scriptHeaders,
            ]
        );

        $this->helperMock
             ->expects($this->never())
             ->method($dontCall);

        $expectedScript = 'const path = "/src/measurement.php?id=G-12345&s=";';
        $expectedResponse = self::createResponse([
            'body' => $expectedScript,
            'headers' => $scriptHeaders,
        ]);
        $this->assertEquals($expectedResponse, $this->measurement->run());
    }

    /**
     * @dataProvider requestBindings
     */
    public function testRespondsWithARootPathScriptRequest(
        $requestCaller,
        $dontCall
    ) {
        $this->createMeasurement('/src/measurement.php?id=G-12345');

        $responseBody = 'const path = "/PHP_GTG_REPLACE_PATH/";';
        $scriptHeaders = ['Content-Type: application/javascript'];
        $this->assertRequestSent(
            [
                'handler' => $requestCaller,
                 'url' => 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/',
            ],
            [
                 'body' => $responseBody,
                 'headers' => $scriptHeaders,
            ]
        );

        $this->helperMock
             ->expects($this->never())
             ->method($dontCall);

        $expectedScript = 'const path = "/src/measurement.php?id=G-12345&s=";';
        $expectedResponse = self::createResponse([
            'body' => $expectedScript,
            'headers' => $scriptHeaders,
        ]);
        $this->assertEquals($expectedResponse, $this->measurement->run());
    }

    /**
     * @dataProvider requestBindings
     */
    public function testSendsScriptRequestWithGeo($requestCaller, $dontCall)
    {
        $this->createMeasurement('/src/measurement.php?id=G-12345&geo=US-CA');

        $expectedRequestUrl = 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/';
        $expectedRequestHeaders = ['x-forwarded-countryregion: US-CA'];

        $responseBody = 'const path = "/PHP_GTG_REPLACE_PATH/";';
        $responseHeaders = ['Content-Type: application/javascript'];
        $this->assertRequestSent(
            [
                'handler' => $requestCaller,
                'url' => $expectedRequestUrl,
                'headers' => $expectedRequestHeaders,
            ],
            [
                'body' => $responseBody,
                'headers' => $responseHeaders,
            ]
        );

        $this->helperMock
             ->expects($this->never())
             ->method($dontCall);

        $expectedScript = 'const path = "/src/measurement.php?id=G-12345&geo=US-CA&s=";';
        $expectedResponse = self::createResponse([
            'body' => $expectedScript,
            'headers' => $responseHeaders,
        ]);
        $this->assertEquals($expectedResponse, $this->measurement->run());
    }

    public function testScriptStripsInvalidGeo()
    {
        $this->createMeasurement('/src/measurement.php?id=G-12345&geo=Not_Geo~format%20invalid');

        $expectedRequestUrl = 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/';
        $expectedRequestHeaders = [/* No geo headers present */];

        $responseBody = 'const path = "/PHP_GTG_REPLACE_PATH/";';
        $responseHeaders = ['Content-Type: application/javascript'];
        $this->assertRequestSent(
            [
                'url' => $expectedRequestUrl,
                'headers' => $expectedRequestHeaders
            ],
            [
                 'body' => $responseBody,
                 'headers' => $responseHeaders,
            ]
        );

        $expectedScript = 'const path = "/src/measurement.php?id=G-12345&s=";';
        $expectedResponse = self::createResponse([
            'body' => $expectedScript,
            'headers' => $responseHeaders,
        ]);
        $this->assertEquals($expectedResponse, $this->measurement->run());
    }

    public function testScriptSendsNoGeoWhenMissing()
    {
        $this->createMeasurement('/src/measurement.php?id=G-12345');

        $expectedRequestUrl = 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/';
        $expectedRequestHeaders = [/* No geo headers present */];

        $responseBody = 'const path = "/PHP_GTG_REPLACE_PATH/";';
        $responseHeaders = ['Content-Type: application/javascript'];
        $this->assertRequestSent(
            [
                'url' => $expectedRequestUrl,
                'headers' => $expectedRequestHeaders
            ],
            [
                 'body' => $responseBody,
                 'headers' => $responseHeaders,
            ]
        );

        $expectedScript = 'const path = "/src/measurement.php?id=G-12345&s=";';
        $expectedResponse = self::createResponse([
            'body' => $expectedScript,
            'headers' => $responseHeaders,
        ]);
        $this->assertEquals($expectedResponse, $this->measurement->run());
    }

    public static function scriptRequestPaths()
    {
        return [
            ['/gtag/js?id=G-12345'],
            ['/gtm.js?id=GTM-12345'],
            ['/?id=AW-12345'],
            ['/random/path?id=MC-12345'],
        ];
    }

    /**
     * @dataProvider scriptRequestPaths
     */
    public function testRespondsWithAScriptRequestForAnyPathWhenContentIsScript($path)
    {
        $this->createMeasurement('/src/measurement.php?id=G-12345&s=' . $path);

        $responseBody = 'const path = "/PHP_GTG_REPLACE_PATH/";';
        $scriptHeaders = ['Content-Type: application/javascript'];

        $this->assertRequestSent(
            [
                'url' =>
                    'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH' . $path
            ],
            [
                 'body' => $responseBody,
                 'headers' => $scriptHeaders,
            ]
        );

        $expectedScript = 'const path = "/src/measurement.php?id=G-12345&s=";';
        $expectedResponse = self::createResponse([
            'body' => $expectedScript,
            'headers' => $scriptHeaders,
        ]);
        $this->assertEquals($expectedResponse, $this->measurement->run());
    }

    /**
     * @param $requestCaller
     * @param $dontCall
     * @return void
     * @dataProvider requestBindings
     */
    public function testRespondsWithAMeasurementRequest(
        $requestCaller,
        $dontCall
    ) {
        $this->createMeasurement('/src/measurement.php?id=G-12345&s=/collect?param1=foo&param2=bar');

        $response = self::createResponse();

        $this->assertRequestSent(
            [
                'handler' => $requestCaller,
                'url' =>
                    'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/collect' .
                    '?param1=foo&param2=bar'
            ],
            $response
        );

        $this->helperMock
             ->expects($this->never())
             ->method($dontCall);

        $this->assertEquals($response, $this->measurement->run());
    }

    public function testSendsMeasurementWithGeo()
    {
        $this->createMeasurement('/src/measurement.php?id=G-12345&geo=US&s=/collect?param1=foo&param2=bar');


        $expectedRequestUrl = 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/collect?param1=foo&param2=bar';
        $expectedRequestHeaders = ['x-forwarded-countryregion: US'];
        $expectedResponse = self::createResponse();

        $this->assertRequestSent(
            [
                'url' => $expectedRequestUrl,
                'headers' => $expectedRequestHeaders,
            ],
            $expectedResponse
        );

        $this->assertEquals($expectedResponse, $this->measurement->run());
    }

    public function testSendsMeasurementWithoutInvalidGeo()
    {
        $this->createMeasurement(
            '/src/measurement.php?id=G-12345' .
            '&geo=Not_your.average%20Geo' .
            '&s=/collect?param1=foo&param2=bar'
        );


        $expectedRequestUrl = 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/collect?param1=foo&param2=bar';
        $expectedRequestHeaders = [/* No geo headers present */];
        $expectedResponse = self::createResponse();

        $this->assertRequestSent(
            [
                'url' => $expectedRequestUrl,
                'headers' => $expectedRequestHeaders,
            ],
            $expectedResponse
        );

        $this->assertEquals($expectedResponse, $this->measurement->run());
    }

    public function testSendsMeasurementWithoutGeoPresent()
    {
        $this->createMeasurement('/src/measurement.php?id=G-12345&s=/collect?param1=foo&param2=bar');


        $expectedRequestUrl = 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/collect?param1=foo&param2=bar';
        $expectedRequestHeaders = [/* No geo headers present */];
        $expectedResponse = self::createResponse();

        $this->assertRequestSent(
            [
                'url' => $expectedRequestUrl,
                'headers' => $expectedRequestHeaders,
            ],
            $expectedResponse
        );

        $this->assertEquals($expectedResponse, $this->measurement->run());
    }


    public function testSendsARequestWithEncodedPath()
    {
        $this->createMeasurement('/src/measurement.php?id=G-12345&s=%2Fgtag%2Fjs%2F&gtm_debug=x');

        $response = self::createResponse();

        $this->assertRequestSent(
            [
                'url' =>
                    'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/gtag/js/' .
                    '?gtm_debug=x',
            ],
            $response
        );

        $this->assertEquals($response, $this->measurement->run());
    }

    public function testSendsARequestWithEncodedPathAndQueryParameters()
    {
        $this->createMeasurement('/src/measurement.php?id=G-12345&s=%2Fgtag%2Fjs%3Fid%3DG-123&gtm_debug=x');

        $response = self::createResponse();

        $this->assertRequestSent(
            [
                'url' =>
                    'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/' .
                    'gtag/js?id=G-123&gtm_debug=x',
            ],
            $response
        );

        $this->assertEquals($response, $this->measurement->run());
    }

    public function testSendsARequestWithMissingQueryParameterStart()
    {
        $this->createMeasurement('/src/measurement.php?id=G-12345&s=/gtag/js&param1=foo&param2=bar');

        $response = self::createResponse();

        $this->assertRequestSent(
            [
                'url' =>
                    'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/' .
                    'gtag/js?param1=foo&param2=bar',
            ],
            $response
        );

        $this->assertEquals($response, $this->measurement->run());
    }

    public function testRespondsWith500ForMissingScriptName()
    {
        $context = new ServerRequestContext(
            [/** Missing script name */],
            ['id' => 'G-12345'],
            ''
        );
        $this->measurement = new Measurement($this->helperMock, $context);
        $this->helperMock
             ->expects($this->once())
             ->method('invalidRequest')
             ->with(500);
        $this->assertEmpty($this->measurement->run());
    }

    public function testAddsAnXForwardedForHeaderWithRemoteAddress()
    {
        $this->createMeasurement(
            '/src/measurement.php?id=G-12345&s=/g/collect?param1=foo&param2=bar',
            ['REMOTE_ADDR' => '192.168.1.1'],
            [/* No X-Forwareded-For header present */],
        );

        $response = self::createResponse();

        $this->assertRequestSent(
            [
                'url' =>
                     'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/g/collect' .
                     '?param1=foo&param2=bar',
                 'headers' => [ 'x-forwarded-for: 192.168.1.1' ],
            ],
            $response
        );

        $this->assertEquals($response, $this->measurement->run());
    }

    public function testAddsAnAdditionalXForwardedForHeaderWithRemoteAddress()
    {
        $this->createMeasurement(
            '/src/measurement.php?id=G-12345&s=/g/collect?param1=foo&param2=bar',
            ['REMOTE_ADDR' => '192.168.1.3'],
            [ 'X-Forwarded-For' => '192.168.1.2' ],
        );

        $response = self::createResponse();

        $this->assertRequestSent(
            [
                'url' =>
                     'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/g/collect' .
                     '?param1=foo&param2=bar',
                 'headers' => [
                     'x-forwarded-for: 192.168.1.2',
                     'x-forwarded-for: 192.168.1.3',
                 ],
            ],
            $response
        );

        $this->assertEquals($response, $this->measurement->run());
    }

    public function testUsesOnlyOriginalXForwardeForIfRemoteAddressIsEmpty()
    {
        $this->createMeasurement(
            '/src/measurement.php?id=G-12345&s=/g/collect?param1=foo&param2=bar',
            [],
            [ 'X-Forwarded-For' => '192.168.1.4' ],
        );

        $response = self::createResponse();

        $this->assertRequestSent(
            [
                'url' =>
                     'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/g/collect' .
                     '?param1=foo&param2=bar',
                 'headers' => [ 'x-forwarded-for: 192.168.1.4' ]
            ],
            $response
        );

        $this->assertEquals($response, $this->measurement->run());
    }

    public function testHasNoXForwardedForHeaderWhenMissing()
    {
        $this->createMeasurement(
            '/src/measurement.php?id=G-12345&s=/g/collect?param1=foo&param2=bar',
            /** No extra server variables or headers present. */
        );

        $response = self::createResponse();

        $this->assertRequestSent(
            [
                'url' =>
                     'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/g/collect' .
                     '?param1=foo&param2=bar',
                 'headers' => [/* No headers present */],
            ],
            $response
        );

        $this->assertEquals($response, $this->measurement->run());
    }

    public function testSendsHealthyRequest()
    {
        $this->createMeasurement('/src/measurement.php?id=G-12345&s=/healthy');

        $response = self::createResponse();

        $this->assertRequestSent(
            ['url' => 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/healthy'],
            $response
        );

        $this->assertEquals($response, $this->measurement->run());
    }

    public static function invalidRequests()
    {
        return [
            'id missing' => ['/src/measurement.php?s=/collect'],
            'no query paramters' => ['/src/measurement.php'],
            'id and source path are missing' => ['/src/measurement.php?foo=bar'],
            'id format is invalid' => ['/src/measurement.php?id=example.com/?x=&s=/collect '],
        ];
    }

    /**
     * @dataProvider invalidRequests
     */
    public function testRespondsWith400ForInvalidRequest($requestUri)
    {
        $this->createMeasurement($requestUri);
        $this->helperMock
             ->expects($this->once())
             ->method('invalidRequest')
             ->with(400);
        $this->assertEmpty($this->measurement->run());
    }

    /**
     * @dataProvider requestBindings
     */
    public function testPassesthroughHeadersOnRequests($requestCaller)
    {
        $this->createMeasurement(
            '/src/measurement.php?id=G-12345&s=/gtag/js',
            [],
            [
              'X-Forwarded-Country' => "US",
              'X-Forwarded-Region' => "CA",
              'X-Test' => "1",
            ],
        );

        $response = self::createResponse();

        $this->assertRequestSent(
            [
                'handler' => $requestCaller,
                'url' => 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/gtag/js',
                'headers' => [
                    'x-forwarded-country: US',
                    'x-forwarded-region: CA',
                    'x-test: 1',
                 ],
            ],
            $response
        );

        $this->assertEquals($response, $this->measurement->run());
    }

    /**
     * @dataProvider requestBindings
     */
    public function testFiltersAllReservedHeaders($requestCaller)
    {
        $this->createMeasurement(
            '/src/measurement.php?id=G-12345&s=/gtag/js',
            [],
            [
              'Accept-Encoding' => 'gzip, deflate, br, zstd',
              'Connection' => 'keep-alive',
              'Content-Length' => '0',
              'Expect' => '100-continue',
              'Host' => 'test.com',
              'Transfer-Encoding' => 'gzip',
              'Authorization' => 'bearer token',
              'Proxy-Authorization' => 'basic credentials',
              'X-Api-Key' => 'an-ap1-k3y',
              'X-Forwarded-Country' => "US",
              'X-Forwarded-Region' => "CA",
              'X-Test' => "1",
            ],
        );

        $response = self::createResponse();

        $this->assertRequestSent(
            [
                'handler' => $requestCaller,
                'url' => 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/gtag/js',
                'headers' => [
                    'x-forwarded-country: US',
                    'x-forwarded-region: CA',
                    'x-test: 1',
                ]
            ],
            $response
        );

        $this->assertEquals($response, $this->measurement->run());
    }

    public static function validPaths()
    {
        return [
          'with a normal path.' => [
            /* scriptName= */'/src/measurement.php',
            /* expectedPath= */'/src/measurement.php',
          ],
          'with special characters in the path.' => [
            /* scriptName= */'/src/my measurement #2 (test & final).php',
            /* expectedPath= */'/src/my%20measurement%20%232%20%28test%20%26%20final%29.php',
          ],
          'with valid relative segments.' => [
            /* scriptName= */'package/../src/measurement.php',
            /* expectedPath= */'/src/measurement.php',
          ],
          'with a url as SCRIPT_NAME.' => [
            /* scriptName= */'https://malicious.com/src/measurement.php',
            /* expectedPath= */'/https%3A/malicious.com/src/measurement.php',
          ],
          'with a Windows like path.' => [
            /* scriptName= */'\src\measurement.php',
            /* expectedPath= */'/src/measurement.php',
          ],
          'with a XSRF attempt.' => [
            /* scriptName= */'\'; console.log("execute malicious script.");',
            /* expectedPath= */'/%27%3B%20console.log%28%22execute%20malicious%20script.%22%29%3B',
          ],
          'with a complex path.' => [
            /* scriptName= */'package/../my_package/./../my package/src/measurement.php',
            /* expectedPath= */'/my%20package/src/measurement.php',
          ],
        ];
    }

    /**
     * @dataProvider validPaths
     */
    public function testSendsCorrectRequestWithServerNameAsUrl($scriptName, $expectedPath)
    {
        $this->createMeasurement(
            '/src/measurement.php?id=G-12345',
            ['SCRIPT_NAME' => $scriptName],
        );

        $responseBody = 'const path = "/PHP_GTG_REPLACE_PATH/";';
        $scriptHeaders = ['Content-Type: application/javascript'];
        $this->assertRequestSent(
            ['url' => 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/'],
            [
                 'body' => $responseBody,
                 'headers' => $scriptHeaders,
            ]
        );

        $expectedScript = "const path = \"{$expectedPath}?id=G-12345&s=\";";
        $this->assertEquals($expectedScript, $this->measurement->run()['body']);
    }

    public static function invalidPaths()
    {
        return [
          'with an empty path' => [''],
          'with trying to traverse outside of root directory.' => ['../../src/measurement.php'],
        ];
    }

    /**
     * @dataProvider invalidPaths
     */
    public function testRespondsWith500ForEmptyScriptName($inputPath)
    {
        $this->createMeasurement('', ['SCRIPT_NAME' => $inputPath]);
        $this->helperMock
             ->expects($this->once())
             ->method('invalidRequest')
             ->with(500);
        $this->assertEmpty($this->measurement->run());
    }

    public function testRespondsWithARedirectLocationMpath()
    {
        $this->createMeasurement('/src/measurement.php?id=G-12345');

        $this->assertRequestSent(
            ['url' => 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/'],
            [
                 'statusCode' => 302,
                 'body' => '',
                 'headers' => ['Location: /PHP_GTG_REPLACE_PATH/?foo=bar'],
            ]
        );

        $expectedResponse = self::createResponse([
            'statusCode' => 302,
            'body' => '',
            'headers' => ['Location: /src/measurement.php?id=G-12345&s=/?foo=bar'],
        ]);
        $this->assertEquals($expectedResponse, $this->measurement->run());
    }

    public function testRespondsWithARedirectLocationMpathAndGeo()
    {
        $this->createMeasurement('/src/measurement.php?id=G-12345&geo=US-CA');

        $this->assertRequestSent(
            [
                'url' => 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/',
                'headers' => ['x-forwarded-countryregion: US-CA'],
            ],
            [
                 'statusCode' => 302,
                 'body' => '',
                 'headers' => ['Location: /PHP_GTG_REPLACE_PATH/?foo=bar'],
            ]
        );

        $expectedResponse = self::createResponse([
            'statusCode' => 302,
            'body' => '',
            'headers' => [
                'Location: ' .
                '/src/measurement.php?id=G-12345&geo=US-CA&s=/?foo=bar',
            ],
        ]);
        $this->assertEquals($expectedResponse, $this->measurement->run());
    }

    public function testRespondsWithOnlyLocationHeaderModified()
    {
        $this->createMeasurement('/src/measurement.php?id=G-12345');

        $this->assertRequestSent(
            ['url' => 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/'],
            [
                 'statusCode' => 302,
                 'body' => '',
                 'headers' => [
                    'Location: /PHP_GTG_REPLACE_PATH/?foo=bar',
                    'Some-Header: true',
                 ],
            ]
        );

        $expectedResponse = self::createResponse([
            'statusCode' => 302,
            'body' => '',
             'headers' => [
                 'Location: /src/measurement.php?id=G-12345&s=/?foo=bar',
                'Some-Header: true',
             ],
        ]);
        $this->assertEquals($expectedResponse, $this->measurement->run());
    }

    public function testRespondsWithOriginalRedirectRequest()
    {
        $this->createMeasurement('/src/measurement.php?id=G-12345&geo=US-CA');

        $redirectHeaders = [
            'Location: /src/measurement.php?id=G-12345&geo=US-CA&s=/?foo=bar',
            'Some-Header: true',
        ];
        $this->assertRequestSent(
            [
                'url' => 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/',
                'headers' => ['x-forwarded-countryregion: US-CA'],
            ],
            [
                 'statusCode' => 302,
                 'body' => '',
                 'headers' => $redirectHeaders,
            ]
        );

        $expectedResponse = self::createResponse([
            'statusCode' => 302,
            'body' => '',
            'headers' => $redirectHeaders,
        ]);
        $this->assertEquals($expectedResponse, $this->measurement->run());
    }


    public function testRespondsWithUnchangedRedirect()
    {
        $this->createMeasurement('/src/measurement.php?id=G-12345&s=/gtag/js');

        $redirectLocation = 'https://www.googletagmanager.com/gtag/js?id=G-12345';
        $redirectHeaders = [
            'Location: ' . $redirectLocation,
            'Some-Header: true',
        ];
        $this->assertRequestSent(
            ['url' => 'https://G-12345.fps.goog/PHP_GTG_REPLACE_PATH/gtag/js'],
            [
                 'statusCode' => 302,
                 'body' => '',
                 'headers' => $redirectHeaders,
            ]
        );

        $expectedResponse = self::createResponse([
            'statusCode' => 302,
            'body' => '',
            'headers' => $redirectHeaders,
        ]);
        $this->assertEquals($expectedResponse, $this->measurement->run());
    }

    public function testSendsARequestWithMeasurementPath()
    {
        $this->createMeasurement(
            '/src/measurement.php?id=G-12345&mpath=test/measurement'
        );
        $this->assertRequestSent(
            ['url' => 'https://G-12345.fps.goog/test/measurement/']
        );

        $this->measurement->run();
    }

    public function testSendsARequestWithMeasurementPathAndDestinationPath()
    {
        $this->createMeasurement(
            '/src/measurement.php?id=G-12345&mpath=test/measurement' .
            '&s=/collect?param1=foo&param2=bar'
        );
        $this->assertRequestSent([
            'url' =>
                'https://G-12345.fps.goog/' .
                'test/measurement/collect?param1=foo&param2=bar'
        ]);

        $this->measurement->run();
    }

    public function testReturnsUnmodifiedMpathInResponseBodyForScript()
    {
        $this->createMeasurement(
            '/src/measurement.php?id=G-12345&mpath=test/measurement'
        );

        $responseBody = 'const path = "/test/measurement/";';
        $this->assertRequestSent(
            ['url' => 'https://G-12345.fps.goog/test/measurement/'],
            [
                'body' => $responseBody,
                'headers' => ['Content-Type: application/javascript'],
            ],
        );

        $result = $this->measurement->run();
        $this->assertEquals($responseBody, $result['body']);
    }

    public function testReturnsUnmodifiedMpathInRedirectResponseLocation()
    {
        $this->createMeasurement(
            '/src/measurement.php?id=G-12345&mpath=test/measurement'
        );

        $redirectLocation = ['Location: /test/measurement/?foo=bar'];
        $this->assertRequestSent(
            ['url' => 'https://G-12345.fps.goog/test/measurement/'],
            [
                 'statusCode' => 302,
                 'body' => '',
                 'headers' => $redirectLocation,
            ],
        );

        $result = $this->measurement->run();
        $this->assertEquals($redirectLocation, $result['headers']);
    }

    /**
     * @dataProvider requestBindings
     */
    public function testSendsPostRequest($requestCaller)
    {
        $this->createMeasurement(
            '/src/measurement.php?id=G-12345&mpath=test/measurement',
            ['REQUEST_METHOD' => 'POST'],
            ['X-Test-Header' => 'true'],
            'name=Frank+Nav&email=boop%40google.com&value=2',
        );

        $response = self::createResponse([
             'statusCode' => 204,
             'body' => '',
             'headers' => [],
        ]);
        $this->assertRequestSent(
            [
            'handler' => $requestCaller,
            'method' => 'POST',
            'url' => 'https://G-12345.fps.goog/test/measurement/',
            'headers' => ['x-test-header: true'],
            'body' => 'name=Frank+Nav&email=boop%40google.com&value=2',
            ],
            $response,
        );

        $result = $this->measurement->run();
        $this->assertEquals($response, $result);
    }

    public function testProperlyPassesEncodedQueryParams()
    {
        $this->createMeasurement(
            '/src/measurement.php?id=G-12345&mpath=test/measurement&s=/test?' .
            'location=https%3A%2F%2Fexample.com%2Ftest%3Ffoo%3D1%202%26bar%3D2' .
            '&bar=%20Bye%3F%3D',
        );

        $this->assertRequestSent(
            [
                'url' =>
                    'https://G-12345.fps.goog/test/measurement/test?location=' .
                    'https%3A%2F%2Fexample.com%2Ftest%3Ffoo%3D1%202%26bar%3D2' .
                    '&bar=%20Bye%3F%3D',
            ]
        );

        $this->measurement->run();
    }


    /**
     * @param string $requestUri
     */
    private static function setGetRequest(string $requestUri)
    {
        $query = parse_url('http://example.com' . $requestUri, PHP_URL_QUERY) ?? '';
        parse_str($query, $get);
        return $get;
    }

    private static function setServerHeaders(array $requestHeaders)
    {
        $headers = [];
        foreach ($requestHeaders as $key => $value) {
            $headerKey = str_replace('-', '_', strtoupper($key));
            $headers["HTTP_$headerKey"] = $value;
        }
        return $headers;
    }

    public function createMeasurement(
        string $requestUri,
        ?array $server = [],
        ?array $requestHeaders = [],
        ?string $body = ''
    ) {
        $get = $this->setGetRequest($requestUri);

        $headers = $this->setServerHeaders($requestHeaders);
        $server =  $server + $headers + ['SCRIPT_NAME' => '/src/measurement.php'];

        $context = new ServerRequestContext($server, $get, $body);

        $this->measurement = new Measurement($this->helperMock, $context);
    }

    /**
     * Assert that a request was sent to the given url.
     *
     * Set the `handler` key in the request to update which handler is used
     * for the request test, can be either:
     * `sendCurlRequest` or `sendFileGetContents`.
     *
     * @param array $request an associative array of the request.
     * @param array $response An associative array of the response.
     */
    private function assertRequestSent(
        array $request,
        array $response = []
    ): void {
        $useHandler = $request['handler'] ?? 'sendCurlRequest';
        $isCurlInstalled = $useHandler === 'sendCurlRequest';

        $this->helperMock
             ->expects($this->once())
             ->method('isCurlInstalled')
             ->willReturn($isCurlInstalled);
        $this->helperMock
             ->expects($this->once())
             ->method($useHandler)
             ->with(
                 $request['method'] ?? 'GET',
                 $request['url'],
                 $request['headers'] ?? [],
                 $request['body'] ?? ''
             )
             ->willReturn(self::createResponse($response));
    }

    /**
     * @param array{
     *      body: string,
     *      statusCode: int,
     *      headers: array<string, string>,
     * } $overrides
     */
    private static function createResponse($overrides = []): array
    {
        $defaultResponse = array(
            'statusCode' => 200,
            'body' => 'ok',
            'headers' => ['X-Test-Header: true'],
        );
        return array_merge($defaultResponse, $overrides);
    }
}
