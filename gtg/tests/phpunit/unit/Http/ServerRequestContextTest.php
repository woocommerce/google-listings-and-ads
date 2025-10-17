<?php

/**
 * ServerRequestContextTest
 *
 * @package   Google\GoogleTagGatewayLibrary\Tests\Http
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Tests\Http;

use Google\GoogleTagGatewayLibrary\Http\ServerRequestContext;
use PHPUnit\Framework\TestCase;

final class ServerRequestContextTest extends TestCase
{
    public function testGetBodyReturnsInitializedBody()
    {
        $body = 'request body value';
        $context = new ServerRequestContext([], [], $body);
        $this->assertEquals($body, $context->getBody());
    }

    public function testGetBodyReturnsEmptyBody()
    {
        $emptyBody = '';
        $context = new ServerRequestContext([], [], $emptyBody);
        $this->assertEquals($emptyBody, $context->getBody());
    }

    public function testGetHeadersReturnsProperlyFormattedHeaders()
    {
        $serverVars = [
            'HTTP_HOST' => 'test.com',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_CACHE_CONTROL' => 'max-age=0',
            'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36', // phpcs:ignore Generic.Files.LineLength.TooLong
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, sdch',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'HTTP_COOKIE' => 'test=cookie',
            'REMOTE_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'test.com',
            'SERVER_PORT' => '80',
        ];
        $context = new ServerRequestContext($serverVars, [], '');
        $expectedHeaders = [
            'host: test.com',
            'connection: keep-alive',
            'cache-control: max-age=0',
            'upgrade-insecure-requests: 1',
            'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36', // phpcs:ignore Generic.Files.LineLength.TooLong
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'accept-encoding: gzip, deflate, sdch',
            'accept-language: en-US,en;q=0.8',
            'cookie: test=cookie',
            'x-forwarded-for: 127.0.0.1',
        ];
        $this->assertEqualsCanonicalizing(
            $expectedHeaders,
            $context->getHeaders(),
        );
    }

    public function testGetHeadersWithSpecialHeaders()
    {
        $serverVars = [
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => '123',
            'CONTENT_MD5' => 'abcdef',
            'REMOTE_ADDR' => '127.0.0.1',
        ];
        $context = new ServerRequestContext($serverVars, [], '');
        $expectedHeaders = [
            'content-type: application/json',
            'content-length: 123',
            'content-md5: abcdef',
            'x-forwarded-for: 127.0.0.1',
        ];
        $this->assertEqualsCanonicalizing(
            $expectedHeaders,
            $context->getHeaders(),
        );
    }

    public function testGetHeadersWithEmptyServer()
    {
        $context = new ServerRequestContext([], [], '');
        $this->assertEquals([], $context->getHeaders());
    }

    public function testGetHeadersWithoutHttpHeaders()
    {
        $serverVars = [
            'SERVER_NAME' => 'test.com',
            'SERVER_PORT' => '80',
            'REMOTE_ADDR' => '192.168.1.1',
        ];
        $context = new ServerRequestContext($serverVars, [], '');
        $expectedHeaders = [
            'x-forwarded-for: 192.168.1.1',
        ];
        $this->assertEqualsCanonicalizing(
            $expectedHeaders,
            $context->getHeaders(),
        );
    }

    public function testGetHeadersWithGeoParam()
    {
        $serverVars = [
            'REMOTE_ADDR' => '127.0.0.1',
        ];
        $queryParams = ['geo' => 'US-CA'];
        $context = new ServerRequestContext($serverVars, $queryParams, '');
        $expectedHeaders = [
            'x-forwarded-for: 127.0.0.1',
            'x-forwarded-countryregion: US-CA',
        ];
        $this->assertEqualsCanonicalizing(
            $expectedHeaders,
            $context->getHeaders(),
        );
    }

    public function testGetHeadersWithInvalidGeoParam()
    {
        $serverVars = [
            'REMOTE_ADDR' => '127.0.0.1',
        ];
        $queryParams = ['geo' => 'US CA']; // Invalid character
        $context = new ServerRequestContext($serverVars, $queryParams, '');
        $expectedHeaders = [
            'x-forwarded-for: 127.0.0.1',
        ];
        $this->assertEqualsCanonicalizing(
            $expectedHeaders,
            $context->getHeaders(),
        );
    }

    public function testGetHeadersWithFilteredHeaders()
    {
        $serverVars = [
            'HTTP_HOST' => 'test.com',
            'HTTP_CONNECTION' => 'keep-alive',
            'REMOTE_ADDR' => '127.0.0.1',
        ];
        $filter = ['HTTP_HOST' => true];
        $context = new ServerRequestContext($serverVars, [], '');
        $expectedHeaders = [
            'connection: keep-alive',
            'x-forwarded-for: 127.0.0.1',
        ];
        $this->assertEqualsCanonicalizing(
            $expectedHeaders,
            $context->getHeaders($filter),
        );
    }

    public function testGetHeadersNormalizesKeys()
    {
        $serverVars = [
            // Should not happen in practice with $_SERVER
            'HttP_CoNnEcTiOn' => 'close',
            'HTTP_USER_AGENT' => 'Test Agent',
            'REMOTE_ADDR' => '127.0.0.1',
        ];
        $context = new ServerRequestContext($serverVars, [], '');
        $expectedHeaders = [
            'user-agent: Test Agent',
            'x-forwarded-for: 127.0.0.1',
        ];
        // Note: HttP_CoNnEcTiOn is not matched because the code specifically
        // looks for 'HTTP_'
        $this->assertEqualsCanonicalizing(
            $expectedHeaders,
            $context->getHeaders(),
        );
    }

    public function testGetMethodReturnsSetValue()
    {
        $serverVars = ['REQUEST_METHOD' => 'POST'];
        $context = new ServerRequestContext($serverVars, [], '');
        $this->assertEquals('POST', $context->getMethod());
    }

    public function testGetMethodDefaultsToGet()
    {
        $context = new ServerRequestContext([], [], '');
        $this->assertEquals('GET', $context->getMethod());
    }

    public function testGetMethodWithEmptyString()
    {
        $serverVars = ['REQUEST_METHOD' => ''];
        $context = new ServerRequestContext($serverVars, [], '');
        $this->assertEquals('GET', $context->getMethod());
    }

    public static function basicValidGeo()
    {
        return [['US-CA', 'GB', '123', '-', 'uS-Ca']];
    }

    /** @dataProvider basicValidGeo */
    public function testGetGeoParamValid($geoValue)
    {
        $queryParams = ['geo' => $geoValue];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals($geoValue, $context->getGeoParam());
    }

    public function testGetGeoParamNotSet()
    {
        $queryParams = [/* no geo parameter set. */];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('', $context->getGeoParam());
    }

    public function testGetGeoParamEmpty()
    {
        $queryParams = ['geo' => ''];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('', $context->getGeoParam());
    }

    public static function invalidGeo()
    {
        return [['US CA', 'US$CA', 'US_CA', '*', '$', 'US&CA']];
    }

    /** @dataProvider invalidGeo */
    public function testGetGeoParamInvalidChars($geoValue)
    {
        $queryParams = ['geo' => $geoValue];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('', $context->getGeoParam());
    }

    public static function basicValidId()
    {
        return [['GTM-123', 'AW-456', 'MC-789', 'gtm-test', '12345', '-']];
    }

    /** @dataProvider basicValidId */
    public function testGetTagIdValid($idValue)
    {
        $queryParams = ['id' => $idValue];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals($idValue, $context->getTagId());
    }

    public function testGetTagIdNotSet()
    {
        $queryParams = [/* no id parameter set. */];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('', $context->getTagId());
    }

    public function testGetTagIdEmpty()
    {
        $queryParams = ['id' => ''];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('', $context->getTagId());
    }

    public static function invalidId()
    {
        return [['GTM 123', 'GTM_123', 'GTM$123', '*', '$', 'GTM&123']];
    }

    /** @dataProvider invalidId */
    public function testGetTagIdInvalidChars($idValue)
    {
        $queryParams = ['id' => $idValue];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('', $context->getTagId());
    }

    public function testGetDestinationNotSet()
    {
        $queryParams = [/* no destination param set. */];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('/', $context->getDestination());
    }

    public function testGetDestinationNotSetWithExtraParameters()
    {
        $queryParams = [
            // No destination param set.
            'foo' => 'bar baz'
        ];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('/?foo=bar%20baz', $context->getDestination());
    }

    public function testGetDestinationEmpty()
    {
        $queryParams = ['s' => ''];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('/', $context->getDestination());
    }

    public function testGetDestinationEmptyWithExtraParameters()
    {
        $queryParams = [
            's' => '',
            'foo' => 'bar baz'
        ];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('/?foo=bar%20baz', $context->getDestination());
    }

    public function testGetDestinationIsSlash()
    {
        $queryParams = ['s' => '/'];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('/', $context->getDestination());
    }

    public function testGetDestinationSimplePath()
    {
        $queryParams = ['s' => '/gtm.js'];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('/gtm.js', $context->getDestination());
    }

    public function testGetDestinationWithOtherParams()
    {
        $queryParams = ['s' => '/gtm.js', 'foo' => 'bar', 'baz' => 'qux'];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals(
            '/gtm.js?foo=bar&baz=qux',
            $context->getDestination(),
        );
    }

    public function testGetDestinationWithReservedParams()
    {
        $queryParams = [
            's' => '/gtm.js',
            'id' => 'GTM-123',
            'geo' => 'US-CA',
            'mpath' => '/mp',
        ];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('/gtm.js', $context->getDestination());
    }

    public function testGetDestinationPathWithExistingQuery()
    {
        $queryParams = ['s' => '/gtm.js?a=b', 'foo' => 'bar'];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('/gtm.js?a=b&foo=bar', $context->getDestination());
    }

    public function testGetDestinationWithMixedParams()
    {
        $queryParams = [
            's' => '/gtm.js',
            'id' => 'GTM-123',
            'foo' => 'bar',
            'geo' => 'US-CA',
            'baz' => 'qux',
        ];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals(
            '/gtm.js?foo=bar&baz=qux',
            $context->getDestination(),
        );
    }

    public function testGetDestinationParamsNeedEncoding()
    {
        $queryParams = [
            's' => '/gtm.js',
            'email' => 'test@example.com',
            'space' => 'foo bar',
        ];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals(
            '/gtm.js?email=test%40example.com&space=foo%20bar',
            $context->getDestination(),
        );
    }

    public function testGetDestinationParamsNeedEncodingWithExistingQuery()
    {
        $queryParams = [
          's' => '/gtm.js?space.val=foo bar',
          'email' => 'test@example.com',
        ];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals(
            '/gtm.js?space.val=foo%20bar&email=test%40example.com',
            $context->getDestination(),
        );
    }

    public function testGetMeasurementPathSet()
    {
        $queryParams = ['mpath' => '/test/path'];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('/test/path', $context->getMeasurementPath());
    }

    public function testGetMeasurementPathNotSet()
    {
        $queryParams = [/* no mpath parameter set. */];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('', $context->getMeasurementPath());
    }

    public function testGetMeasurementPathEmpty()
    {
        $queryParams = ['mpath' => ''];
        $context = new ServerRequestContext([], $queryParams, '');
        $this->assertEquals('', $context->getMeasurementPath());
    }
}
