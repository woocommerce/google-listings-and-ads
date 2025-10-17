<?php

/**
 * TestCase
 *
 * @package   Google\GoogleTagGatewayLibrary\Tests
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;

class TestCase extends PHPUnitTestCase
{
    // Adds Mockery expectations to the PHPUnit assertions count.
    use MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
