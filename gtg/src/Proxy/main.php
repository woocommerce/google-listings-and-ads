<?php

/**
 * The entrypoint for the proxy script to run all logic.
 *
 * @package   Google\GoogleTagGatewayLibrary\Proxy
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

use Google\GoogleTagGatewayLibrary\Proxy\Runner;

Runner::create()->run();
