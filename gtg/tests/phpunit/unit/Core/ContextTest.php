<?php

/**
 * ContextTest
 *
 * @package   Google\GoogleTagGatewayLibrary\Tests\Core\Context
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Tests\Core;

use Google\GoogleTagGatewayLibrary\Core\Context;
use Google\GoogleTagGatewayLibrary\Exceptions\InvalidMeasurementPathException;
use Google\GoogleTagGatewayLibrary\Tests\TestCase;

final class ContextTest extends TestCase
{
    public function testGetMeasurementPhpFilePathReturnsFullPath()
    {
        $context = new Context(
            '/test/dir', /* libraryRoot */
            '', /* documentRoot */
        );
        $result = $context->getMeasurementPhpFilePath();
        $this->assertEquals('/test/dir/dist/measurement.php', $result);
    }

    public function testGetMeasurementPhpUrlPathReturnsUrlPath()
    {
        $context = new Context(
            '/var/www/html/stuff/gtg-library', /* libraryRoot */
            '/var/www/html', /* documentRoot */
        );
        $result = $context->getMeasurementPhpUrlPath();
        $this->assertEquals('/stuff/gtg-library/dist/measurement.php', $result);
    }

    public function testGetMeasurementPhpUrlPathReturnsPathWithTrailingSlash()
    {
        $context = new Context(
            '/var/www/html/stuff/gtg-library', /* libraryRoot */
            '/var/www/html/', /* documentRoot */
        );
        $result = $context->getMeasurementPhpUrlPath();
        $this->assertEquals('/stuff/gtg-library/dist/measurement.php', $result);
    }

    public function testGetMeasurementPhpUrlPathThrowsWithEmptyDocRoot()
    {
        $context = new Context(
            '/var/www/html/stuff/gtg-library', /* libraryRoot */
            '', /* documentRoot */
        );

        $this->expectException(InvalidMeasurementPathException::class);
        $context->getMeasurementPhpUrlPath();
    }

    public function testGetMeasurementPhpUrlPathThrowsWithEmptyLibRoot()
    {
        $context = new Context(
            '', /* libraryRoot */
            '/var/www/html', /* documentRoot */
        );

        $this->expectException(InvalidMeasurementPathException::class);
        $context->getMeasurementPhpUrlPath();
    }

    public function testGetMeasurementPhpUrlPathThrowsWithEmpytLibAndDoc()
    {
        $context = new Context(
            '', /* libraryRoot */
            '', /* documentRoot */
        );

        $this->expectException(InvalidMeasurementPathException::class);
        $context->getMeasurementPhpUrlPath();
    }

    public function testGetMeasurementPhpUrlPathThrowsWithMismatchDirs()
    {
        $context = new Context(
            '/usr/local/php/stuff/gtg-library', /* libraryRoot */
            '/var/www/html', /* documentRoot */
        );

        $this->expectException(InvalidMeasurementPathException::class);
        $context->getMeasurementPhpUrlPath();
    }
}
