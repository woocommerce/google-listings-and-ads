<?php

/**
 * Class Google\GoogleTagGatewayLibrary\Exceptions\InvalidMeasurementPathException
 *
 * @package   Google\GoogleTagGatewayLibrary
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Exceptions;

use Exception;

final class InvalidMeasurementPathException extends Exception
{
    public function __construct($errorMessage)
    {
        parent::__construct(
            "While attempting to construct the measurement path an error was encountered: " . $errorMessage
        );
    }
}
