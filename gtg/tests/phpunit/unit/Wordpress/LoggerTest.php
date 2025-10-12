<?php

/**
 * LoggerTest
 *
 * @package   Google\GoogleTagGatewayLibrary\Tests\Wordpress\Logger
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Tests\Wordpress;

use Brain\Monkey\Functions;
use Google\GoogleTagGatewayLibrary\Tests\TestCase;
use Google\GoogleTagGatewayLibrary\Wordpress\Logger;

final class LoggerTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoesNotLogWhenDebugNotDefined()
    {
        Functions\expect('error_log')->never();
        $logger = new Logger();
        $logger->log("no debug defined log");
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoesNotLogWhenDebugIsNotTrue()
    {
        define('WP_DEBUG', 1);
        Functions\expect('error_log')->never();
        $logger = new Logger();
        $logger->log("WP_DEBUG is not true");
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoesLogWhenDebugIsTrue()
    {
        define('WP_DEBUG', true);
        Functions\expect('error_log')->once()->with('log success!');
        $logger = new Logger();
        $logger->log("log success!");
    }
}
