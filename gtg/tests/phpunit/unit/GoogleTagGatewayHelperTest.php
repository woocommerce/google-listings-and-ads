<?php

/**
 * GoogleTagGatewayHelperTest
 *
 * @package   Google\GoogleTagGatewayLibrary\Tests
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Tests;

use Google\GoogleTagGatewayLibrary\GoogleTagGatewayHelper;
use Google\GoogleTagGatewayLibrary\Exceptions\InvalidContainerIdException;
use Google\GoogleTagGatewayLibrary\Exceptions\InvalidMeasurementPathException;
use Google\GoogleTagGatewayLibrary\Http\RequestHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GoogleTagGatewayHelperTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|RequestHelper */
    private $helperMock;

    protected function setUp(): void
    {
        // Reset all the projects server variables for testing.
        $_SERVER = [];
        $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 3);
        $_SERVER['HTTPS'] = '';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['SERVER_NAME'] = 'localhost';

        $this->helperMock = $this->createMock(RequestHelper::class);
    }

    /**
     * @param string $containerId
     * @return void
     * @dataProvider validIdsFormatted
     */
    public function testContainsAllResources($containerId): void
    {
        $helper = new GoogleTagGatewayHelper($containerId);
        $resources = $helper->createResources();

        $this->assertArrayHasKey("script", $resources);
        $this->assertArrayHasKey("src", $resources);
        $this->assertArrayHasKey("topScript", $resources);
    }

    /**
     * @param string $containerId
     * @return void
     * @dataProvider validIdsFormatted
     */
    public function testGtagContainsAllResourcesWithCustomMpath(
        string $containerId
    ): void {
        $helper = new GoogleTagGatewayHelper($containerId, [
            'mpath' => '/test/measurement',
        ]);
        $resources = $helper->createResources();

        $this->assertArrayHasKey("script", $resources);
        $this->assertArrayHasKey("src", $resources);
        $this->assertArrayHasKey("topScript", $resources);
    }

    /**
     * @param string $containerId
     * @return void
     * @dataProvider validContainerIds
     */
    public function testContainsCorrectScript(string $containerId): void
    {
        $helper = new GoogleTagGatewayHelper($containerId);
        $resources = $helper->createResources();

        $this->assertEquals(
            self::normalizeSpace("
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag('set', 'developer_id.dYmZiZj', true);
              gtag('js', new Date());
              gtag('config', '{$containerId}');"),
            self::normalizeSpace($resources['script'])
        );
    }

    /**
     * @param string $containerId
     * @return void
     * @dataProvider validContainerIds
     */
    public function testContainsCorrectScriptWithMpath(
        string $containerId
    ): void {
        $helper = new GoogleTagGatewayHelper($containerId, [
            'mpath' => '/test/measurement',
        ]);
        $resources = $helper->createResources();

        $this->assertEquals(
            self::normalizeSpace("
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag('set', 'developer_id.dYmZiZj', true);
              gtag('js', new Date());
              gtag('config', '{$containerId}');"),
            self::normalizeSpace($resources['script'])
        );
    }

    /**
     * @param string $containerId
     * @return void
     * @dataProvider validContainerIds
     */
    public function testContainsCorrectSrc($containerId): void
    {
        $helper = new GoogleTagGatewayHelper($containerId);
        $resources = $helper->createResources();

        $this->assertEquals("/dist/measurement.php?id={$containerId}", $resources['src']);
    }

    public static function validMpaths()
    {
        return [
            ["path/123/view","/path/123/view/"],
            ["cats","/cats/"],
            ["user/test","/user/test/"],
            ["a/b/c/d/1/2/3","/a/b/c/d/1/2/3/"],
            ["/leadingslash","/leadingslash/"],
            ["trailingslash/","/trailingslash/"],
            ["single/","/single/"],
        ];
    }

    /**
     * @param string $mpath
     * @return void
     * @dataProvider validMpaths
     */
    public function testReturnsTrueForValidMpath(string $mpath)
    {
        $this->assertTrue(GoogleTagGatewayHelper::validateMpath($mpath));
    }

    /**
     * @param string $mpath
     * @return void
     * @dataProvider validMpaths
     */
    public function testContainsCorrectSrcWithMpath(
        string $mpath,
        string $srcMpath
    ): void {
        $helper = new GoogleTagGatewayHelper('G-12345', ['mpath' => $mpath]);
        $resources = $helper->createResources();

        $this->assertEquals($srcMpath, $resources['src']);
    }

    public function testContainsDirectSrcWithEmptyMpath(): void
    {
        $helper = new GoogleTagGatewayHelper('G-EMPTYMPATH', ['mpath' => '']);
        $resources = $helper->createResources();

        $this->assertEquals(
            "/dist/measurement.php?id=G-EMPTYMPATH",
            $resources['src'],
        );
    }

    public static function invalidMpaths(): array
    {
        return [
            ["path//123"],          // Consecutive slashes
            ["cats//"],             // Consecutive slashes at the end
            ["//start"],            // Consecutive slashes at the beginning
            ["path//to/m"],         // Consecutive slashes in the middle
            ["invalid-chars"],      // Contains a hyphen
            ["has spaces"],         // Contains a space
            ["special*char"],       // Contains an asterisk
            ["test/trailingspace "], // Contains trailing space
            ["/"],                  // Single slash is invalid
            [" leading/space"],     // Contains a leading space
            ["new\nline"],          // Contains a newline character
            ["tab\tchar"],           // Contains a tab character
            // 99 characters long + leading and trailing '/'
            [
                "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa" .
                "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"
            ],
            // 101 characters long
            [
                "/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa" .
                "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/"
            ],
        ];
    }

    /**
     * @param string $mpath
     * @return void
     * @dataProvider invalidMpaths
     */
    public function testValidateMpathReturnsFalseForInvalidMpath(
        string $mpath
    ): void {
        $this->assertFalse(GoogleTagGatewayHelper::validateMpath($mpath));
    }

    public function testCreateResourcesThrowsWithInvalidMpath(): void
    {
        $helper = new GoogleTagGatewayHelper('G-12345', ['mpath' => 'cats///']);

        $this->expectException(InvalidMeasurementPathException::class);
        $helper->createResources();
    }

    public function testHealthCheckFailsWithAnErrorMessageWithInvalidMpath(): void
    {
        $helper = new GoogleTagGatewayHelper('G-12345', ['mpath' => 'cats///']);

        $results = $helper->healthCheck();

        $this->assertFalse($results['status']);
        $this->assertStringContainsString(
            'not a valid url path',
            $results['errorMessage'],
        );
    }

    /**
     * @param string $containerId
     * @return void
     * @dataProvider validContainerIds
     */
    public function testContainsCorrectTopScript($containerId): void
    {
        $helper = new GoogleTagGatewayHelper($containerId);
        $resources = $helper->createResources();

        $this->assertEquals(
            self::normalizeSpace("
                (function(w,i,g){w[g]=w[g]||[];if(typeof w[g].push=='function')w[g].push.apply(w[g], i)})
                (window,['{$containerId}'],'google_tags_first_party');"),
            self::normalizeSpace($resources['topScript'])
        );
    }

    public function testCreateResourcesThrowsWithInvalidContainerId(): void
    {
        $badContainerId = "<script>
            console.log('This is a malicious script.');
        </script>";
        $helper = new GoogleTagGatewayHelper($badContainerId);

        $this->expectException(InvalidContainerIdException::class);
        $helper->createResources();
    }

    public function testHealthCheckFailsWithInvalidContainerId(): void
    {
        $badContainerId = "<script>
            console.log('This is a malicious script.');
        </script>";
        $helper = new GoogleTagGatewayHelper($badContainerId);

        $results = $helper->healthCheck();

        $this->assertFalse($results['status']);
        $this->assertStringContainsString(
            'not a valid container ID',
            $results['errorMessage'],
        );
    }

    public function testCreateResourcesThrowsWithInvalidDocRootConfiguration(): void
    {
        unset($_SERVER['DOCUMENT_ROOT']);
        $helper = new GoogleTagGatewayHelper("AW-12B45");

        $this->expectException(InvalidMeasurementPathException::class);
        $helper->createResources();
    }

    public function testHealthCheckFailsWithInvalidDocRootConfiguration(): void
    {
        unset($_SERVER['DOCUMENT_ROOT']);
        $helper = new GoogleTagGatewayHelper("AW-12B45");

        $results = $helper->healthCheck();

        $this->assertFalse($results['status']);
        $this->assertStringContainsString(
            '$_SERVER["DOCUMENT_ROOT"]',
            $results['errorMessage'],
        );
    }

    public static function validPaths()
    {
        return [
          'a normal path.' => [
            /* documentRoot= */'/var/www/html',
            /* path= */'/var/www/html/dist/measurement.php',
            /* expectedPath= */'/dist/measurement.php',
          ],
          'special characters in the path.' => [
            /* documentRoot= */'/var/www/html',
            /* path= */'/var/www/html/dist/my measurement #2 (test & final).php',
            /* expectedPath= */'/dist/my%20measurement%20%232%20%28test%20%26%20final%29.php',
          ],
          'valid relative segments.' => [
            /* documentRoot= */'/var/www/html',
            /* path= */'/var/www/html/package/../dist/measurement.php',
            /* expectedPath= */'/dist/measurement.php',
          ],
          'a url as SCRIPT_NAME.' => [
            /* documentRoot= */'/var/www/html',
            /* path= */'/var/www/html/https://malicious.com/dist/measurement.php',
            /* expectedPath= */'/https%3A/malicious.com/dist/measurement.php',
          ],
          'a Windows like path.' => [
            /* documentRoot= */'C:\var\www\html',
            /* path= */'C:\var\www\html\dist\measurement.php',
            /* expectedPath= */'/dist/measurement.php',
          ],
          'a XSRF attempt.' => [
            /* documentRoot= */'/var/www/html',
            /* path= */'/var/www/html/\'; console.log("execute malicious script.");',
            /* expectedPath= */'/%27%3B%20console.log%28%22execute%20malicious%20script.%22%29%3B',
          ],
          'a complex path.' => [
            /* documentRoot= */'/var/www/html',
            /* path= */'/var/www/html/package/../my_package/./../my package/dist/measurement.php',
            /* expectedPath= */'/my%20package/dist/measurement.php',
          ],
        ];
    }

    /**
     * @dataProvider validPaths
     */
    public function testGeneratesCorrectSourceWith($documentRoot, $path, $expectedPath)
    {
        $helper = new GoogleTagGatewayHelper("AW-12B45");

        $_SERVER['DOCUMENT_ROOT'] = $documentRoot;
        $helper->setDefaultPhpRedirectorFile($path);

        $resources = $helper->createResources();

        $this->assertEquals("{$expectedPath}?id=AW-12B45", $resources['src']);
    }

    public static function invalidPaths(): array
    {
        return [
            'path resolves to empty.' => [
                /* documentRoot= */'/var/www/html',
                /* path= */'/var/www/html',
            ],
            'trying to traverse outside of the root directory.' => [
                /* documentRoot= */'/var/www/html',
                /* path= */'/var/www/html/pacakge/../../dist/measurement.php',
            ],
            'document root is not present in the file path.' => [
                /* documentRoot= */'/var/www/html',
                /* path= */'/home/user/pacakge/../../dist/measurement.php',
            ],
        ];
    }

    /**
     * @dataProvider invalidPaths
     */
    public function testParsingPathInCreateResourcesThrowsWithInvalidMeaurementPathAnd(
        string $documentRoot,
        string $path
    ): void {
        $_SERVER['DOCUMENT_ROOT'] = $documentRoot;

        $helper = new GoogleTagGatewayHelper("AW-12B45");
        $helper->setDefaultPhpRedirectorFile($path);

        $this->expectException(InvalidMeasurementPathException::class);
        $helper->createResources();
    }

    /**
     * @dataProvider invalidPaths
     */
    public function testParsingPathInHealthCheckFailsWithInvalidMeaurementPathAnd(
        string $documentRoot,
        string $path
    ): void {
        $_SERVER['DOCUMENT_ROOT'] = $documentRoot;

        $helper = new GoogleTagGatewayHelper("AW-12B45");
        $helper->setDefaultPhpRedirectorFile($path);

        $results = $helper->healthCheck();

        $this->assertFalse($results['status']);
        $this->assertStringContainsString(
            'Could not properly parse the measurement path',
            $results['errorMessage'],
        );
    }

    public function testGeneratesResourcesWithUnidentifiableContainerId(): void
    {
        $helper = new GoogleTagGatewayHelper("AW-12B345");
        $resources = $helper->createResources();

        $this->assertArrayHasKey("script", $resources);
        $this->assertArrayHasKey("src", $resources);
    }

    public function testUnidentifiedContainerContainsGtagScript(): void
    {
        $helper = new GoogleTagGatewayHelper("AW-12B45");
        $resources = $helper->createResources();

        $this->assertEquals(
            self::normalizeSpace("
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag('set', 'developer_id.dYmZiZj', true);
              gtag('js', new Date());
              gtag('config', 'AW-12B45');"),
            self::normalizeSpace($resources['script'])
        );
    }

    public function testUnidentifiedContainerContainsGtagSrc(): void
    {
        $helper = new GoogleTagGatewayHelper("AW-12B45");
        $resources = $helper->createResources();

        $this->assertEquals("/dist/measurement.php?id=AW-12B45", $resources['src']);
    }

    public function testHealthCheckPasses(): void
    {
        $_SERVER['SERVER_NAME'] = 'example.com';
        $this->helperMock
             ->expects($this->once())
             ->method('sendRequest')
             ->with(
                 'GET',
                 'http://example.com/dist/measurement.php?id=AW-12B45&s=/healthy',
             )
             ->willReturn(self::createResponse());

        $helper = new GoogleTagGatewayHelper("AW-12B45");
        $helper->setRequestHelper($this->helperMock);
        $results = $helper->healthCheck();

        $this->assertTrue($results['status']);
        $this->assertArrayNotHasKey('errorMessage', $results);
    }

    public function testHealthCheckPassesWithCustomMpath(): void
    {
        $_SERVER['SERVER_NAME'] = 'example.com';
        $this->helperMock
             ->expects($this->once())
             ->method('sendRequest')
             ->with('GET', 'http://example.com/custom/m/?s=/healthy')
             ->willReturn(self::createResponse());

        $helper = new GoogleTagGatewayHelper("AW-12B45", [
            'mpath' => 'custom/m',
        ]);
        $helper->setRequestHelper($this->helperMock);
        $results = $helper->healthCheck();

        $this->assertTrue($results['status']);
        $this->assertArrayNotHasKey('errorMessage', $results);
    }

    public function testHealthCheckPassesWithHttps(): void
    {
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['HTTPS'] = true;
        $this->helperMock
             ->expects($this->once())
             ->method('sendRequest')
             ->with(
                 'GET',
                 'https://example.com/dist/measurement.php?id=AW-12B45&s=/healthy',
             )
             ->willReturn(self::createResponse());

        $helper = new GoogleTagGatewayHelper("AW-12B45");
        $helper->setRequestHelper($this->helperMock);
        $results = $helper->healthCheck();

        $this->assertTrue($results['status']);
        $this->assertArrayNotHasKey('errorMessage', $results);
    }

    public function testHealthCheckFailsWithInvalidResponseBody(): void
    {
        $_SERVER['SERVER_NAME'] = 'example.com';
        $this->helperMock
             ->expects($this->once())
             ->method('sendRequest')
             ->with(
                 'GET',
                 'http://example.com/dist/measurement.php?id=AW-12B45&s=/healthy',
             )
             ->willReturn(self::createResponse(["body" => ""]));

        $helper = new GoogleTagGatewayHelper("AW-12B45");
        $helper->setRequestHelper($this->helperMock);
        $results = $helper->healthCheck();

        $this->assertFalse($results['status']);
        $this->assertIsString($results['errorMessage']);
    }

    public function testHealthCheckFailsWithInvalidResponseCode(): void
    {
        $_SERVER['SERVER_NAME'] = 'example.com';
        $this->helperMock
             ->expects($this->once())
             ->method('sendRequest')
             ->with(
                 'GET',
                 'http://example.com/dist/measurement.php?id=AW-12B45&s=/healthy',
             )
             ->willReturn(self::createResponse(["statusCode" => 500]));

        $helper = new GoogleTagGatewayHelper("AW-12B45");
        $helper->setRequestHelper($this->helperMock);
        $results = $helper->healthCheck();

        $this->assertFalse($results['status']);
        $this->assertIsString($results['errorMessage']);
    }

    public function testHealthCheckFailsWithMissingServerName(): void
    {
        unset($_SERVER['SERVER_NAME']);

        $helper = new GoogleTagGatewayHelper("AW-12B45");
        $results = $helper->healthCheck();

        $this->assertFalse($results['status']);
        $this->assertIsString($results['errorMessage']);
    }

    public function testHealthCheckFailsWithMissingScriptName(): void
    {
        unset($_SERVER['SCRIPT_NAME']);

        $helper = new GoogleTagGatewayHelper("AW-12B45");
        $results = $helper->healthCheck();

        $this->assertFalse($results['status']);
        $this->assertIsString($results['errorMessage']);
    }

    /**
     * @param string $containerId
     * @return void
     * @dataProvider validContainerIds
     */
    public function testGeoContainsOnlyScript($containerId): void
    {
        $helper = new GoogleTagGatewayHelper($containerId, ['geoFunction' => 'getGeoOnPage']);
        $resources = $helper->createResources();

        $this->assertArrayHasKey("script", $resources);
        $this->assertEmpty($resources['src']);
    }

    /**
     * @param string $containerId
     * @return void
     * @dataProvider validContainerIds
     */
    public function testContainsGeoScript($containerId): void
    {
        $helper = new GoogleTagGatewayHelper(
            $containerId,
            ['geoFunction' => 'getGeoOnPage']
        );
        $resources = $helper->createResources();

        $this->assertEquals(
            self::normalizeSpace("
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('set', 'developer_id.dYmZiZj', true);
                gtag('js', new Date());

                (function (d, s, i) {
                  gtag('config', i);
                  function create(gr) {
                    gr = (gr || '').replace(/[^A-Za-z0-9\-]/g, '');
                    var f = d.getElementsByTagName(s)[0],
                      j = d.createElement(s),
                      gp = gr ? '&geo=' + gr : '';
                    j.async = true;
                    j.src = '/dist/measurement.php?id={$containerId}' + gp;
                    f.parentNode.insertBefore(j, f);
                  }
                  Promise.race([
                    new Promise((resolve) => resolve(getGeoOnPage())),
                    new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 500))
                  ]).then(create)
                    .catch(() => create(''));
                })(document, 'script', '{$containerId}');
            "),
            self::normalizeSpace($resources['script'])
        );
    }

    public function testContainsGeoScriptWithCustomMpath(): void
    {
        $helper = new GoogleTagGatewayHelper('G-12345', [
            'geoFunction' => 'getGeoOnPage',
            'mpath' => 'path/to/measure',
        ]);
        $resources = $helper->createResources();

        $this->assertEquals(
            self::normalizeSpace("
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('set', 'developer_id.dYmZiZj', true);
                gtag('js', new Date());

                (function (d, s, i) {
                  gtag('config', i);
                  function create(gr) {
                    gr = (gr || '').replace(/[^A-Za-z0-9\-]/g, '');
                    var f = d.getElementsByTagName(s)[0],
                      j = d.createElement(s),
                      gp = gr ? '?geo=' + gr : '';
                    j.async = true;
                    j.src = '/path/to/measure/' + gp;
                    f.parentNode.insertBefore(j, f);
                  }
                  Promise.race([
                    new Promise((resolve) => resolve(getGeoOnPage())),
                    new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 500))
                  ]).then(create)
                    .catch(() => create(''));
                })(document, 'script', 'G-12345');
            "),
            self::normalizeSpace($resources['script'])
        );
    }

    public function testContainsGeoScriptWithDirectWhenMpathIsEmpty(): void
    {
        $helper = new GoogleTagGatewayHelper('G-12345', [
            'geoFunction' => 'getGeoOnPage',
            'mpath' => '',
        ]);
        $resources = $helper->createResources();

        $this->assertEquals(
            self::normalizeSpace("
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('set', 'developer_id.dYmZiZj', true);
                gtag('js', new Date());

                (function (d, s, i) {
                  gtag('config', i);
                  function create(gr) {
                    gr = (gr || '').replace(/[^A-Za-z0-9\-]/g, '');
                    var f = d.getElementsByTagName(s)[0],
                      j = d.createElement(s),
                      gp = gr ? '&geo=' + gr : '';
                    j.async = true;
                    j.src = '/dist/measurement.php?id=G-12345' + gp;
                    f.parentNode.insertBefore(j, f);
                  }
                  Promise.race([
                    new Promise((resolve) => resolve(getGeoOnPage())),
                    new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 500))
                  ]).then(create)
                    .catch(() => create(''));
                })(document, 'script', 'G-12345');
            "),
            self::normalizeSpace($resources['script'])
        );
    }

    public function testUsesGeoWhenUnknownHeaderIsPresent()
    {
        $_SERVER['HTTP_X_RANDOM_GEO_HEADER'] = 'US';
        $helper = new GoogleTagGatewayHelper('G-12345', ['geoFunction' => 'getGeoOnPage']);
        $resources = $helper->createResources();

        $this->assertEquals(
            self::normalizeSpace("
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('set', 'developer_id.dYmZiZj', true);
                gtag('js', new Date());

                (function (d, s, i) {
                  gtag('config', i);
                  function create(gr) {
                    gr = (gr || '').replace(/[^A-Za-z0-9\-]/g, '');
                    var f = d.getElementsByTagName(s)[0],
                      j = d.createElement(s),
                      gp = gr ? '&geo=' + gr : '';
                    j.async = true;
                    j.src = '/dist/measurement.php?id=G-12345' + gp;
                    f.parentNode.insertBefore(j, f);
                  }
                  Promise.race([
                    new Promise((resolve) => resolve(getGeoOnPage())),
                    new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 500))
                  ]).then(create)
                    .catch(() => create(''));
                })(document, 'script', 'G-12345');
            "),
            self::normalizeSpace($resources['script'])
        );
    }

    /**
     * @param string $containerId
     * @param string $geoHeader
     * @return void
     * @dataProvider containerIdsWithGeoHeader
     */
    public function testNoGeoWhenKnownHeaderIsPresent($containerId, $geoHeader)
    {
        $_SERVER[$geoHeader] = 'US';
        $helper = new GoogleTagGatewayHelper($containerId, ['geoFunction' => 'getGeoOnPage']);
        $resources = $helper->createResources();

        $this->assertStringNotContainsString('&geo=', $resources['script']);
    }

    /**
     * @param string $containerId
     * @return void
     * @dataProvider validContainerIds
     */
    public function testNoGeoWhenFunctionIsEmpty($containerId)
    {
        $helper = new GoogleTagGatewayHelper($containerId, ['geoFunction' => '']);
        $resources = $helper->createResources();

        $this->assertStringNotContainsString($resources['script'], '&geo=');
    }

    public static function validContainerIds()
    {
        return [
            ['G-12345'],
            ['GT-12345'],
            ['GTM-12345'],
            ['AW-12345'],
            ['MC-12345'],
        ];
    }

    public static function validIdsFormatted()
    {
        return [
          ['g-12345'],
          ['gt-12345'],
          ['G-123ABC'],
          ['G-123abC'],
          ['g-123Abc'],
          ['gtm-12345'],
          ['GTM-123ABC'],
          ['Gtm-123abC'],
          ['gTm-123Abc'],
        ] + self::validContainerIds();
    }

    public static function containerIdsWithGeoHeader()
    {
        return [
            ['G-12345', 'HTTP_X_FORWARDED_COUNTRY'],
            ['G-12345', 'HTTP_X_FORWARDED_COUNTRYREGION'],
            ['G-12345', 'HTTP_CF_IPCOUNTRY'],
            ['G-12345', 'HTTP_X_CFIPCOUNTRYREGION'],
            ['G-12345', 'HTTP_CF_IPCOUNTRYREGION'],
            ['G-12345', 'HTTP_X_GCLB_COUNTRY'],
            ['G-12345', 'HTTP_X_AKAMAI_EDGESCAPE'],
            ['G-12345', 'HTTP_X_AZURE_COUNTRY'],
            ['G-12345', 'HTTP_CLOUDFRONT_VIEWER_COUNTRY'],

            ['GT-12345', 'HTTP_X_FORWARDED_COUNTRY'],
            ['GT-12345', 'HTTP_X_FORWARDED_COUNTRYREGION'],
            ['GT-12345', 'HTTP_CF_IPCOUNTRY'],
            ['GT-12345', 'HTTP_X_CFIPCOUNTRYREGION'],
            ['GT-12345', 'HTTP_CF_IPCOUNTRYREGION'],
            ['GT-12345', 'HTTP_X_GCLB_COUNTRY'],
            ['GT-12345', 'HTTP_X_AKAMAI_EDGESCAPE'],
            ['GT-12345', 'HTTP_X_AZURE_COUNTRY'],
            ['GT-12345', 'HTTP_CLOUDFRONT_VIEWER_COUNTRY'],

            ['AW-12345', 'HTTP_X_FORWARDED_COUNTRY'],
            ['AW-12345', 'HTTP_X_FORWARDED_COUNTRYREGION'],
            ['AW-12345', 'HTTP_CF_IPCOUNTRY'],
            ['AW-12345', 'HTTP_X_CFIPCOUNTRYREGION'],
            ['AW-12345', 'HTTP_CF_IPCOUNTRYREGION'],
            ['AW-12345', 'HTTP_X_GCLB_COUNTRY'],
            ['AW-12345', 'HTTP_X_AKAMAI_EDGESCAPE'],
            ['AW-12345', 'HTTP_X_AZURE_COUNTRY'],
            ['AW-12345', 'HTTP_CLOUDFRONT_VIEWER_COUNTRY'],

            ['MC-12345', 'HTTP_X_FORWARDED_COUNTRY'],
            ['MC-12345', 'HTTP_X_FORWARDED_COUNTRYREGION'],
            ['MC-12345', 'HTTP_CF_IPCOUNTRY'],
            ['MC-12345', 'HTTP_X_CFIPCOUNTRYREGION'],
            ['MC-12345', 'HTTP_CF_IPCOUNTRYREGION'],
            ['MC-12345', 'HTTP_X_GCLB_COUNTRY'],
            ['MC-12345', 'HTTP_X_AKAMAI_EDGESCAPE'],
            ['MC-12345', 'HTTP_X_AZURE_COUNTRY'],
            ['MC-12345', 'HTTP_CLOUDFRONT_VIEWER_COUNTRY'],
        ];
    }

    /**
     * Remove excessive spacing to test that strings have the same value.
     *
     * @param string $formatString
     */
    private static function normalizeSpace($formatString)
    {
        return preg_replace('/\s+/', ' ', trim($formatString));
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
