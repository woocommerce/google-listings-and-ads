<?php

/**
 * Class Google\GoogleTagGatewayLibrary\Wordpress\Manager
 *
 * @pacakge   Google\GoogleTagGatewayLibrary\Wordpress
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Wordpress;

class Logger
{
    /**
     * Logs to the PHP error log location, only if WP_DEBUG is enabled.
     *
     * @parm string $message Message to be logged
     */
    public function log(string $message): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log($message);
        }
    }
}
