<?php

/**
 * Class Google\GoogleTagGatewayLibrary\Exceptions\InvalidContainerIdException
 *
 * @package   Google\GoogleTagGatewayLibrary
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Exceptions;

use Exception;

final class InvalidContainerIdException extends Exception
{
    public function __construct($containerId)
    {
        parent::__construct(
            "The container ID provided is not a valid container ID: " . $containerId
        );
    }
}
