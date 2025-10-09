<?php

/**
 * Class Google\GoogleTagGatewayLibrary\Core\Context
 *
 * @package   Google\GoogleTagGatewayLibrary\Core
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Core;

use Google\GoogleTagGatewayLibrary\Exceptions\InvalidMeasurementPathException;
use Google\GoogleTagGatewayLibrary\Http\RequestHelper;

class Context
{
    /**
     * The relative path from the library root to the measurement.php proxy
     * file.
     *
     * @var string
     */
    private const RELATIVE_MEASUREMENT_PHP = '/dist/measurement.php';

    /**
     * The root directory of the library.
     *
     * @var string
     */
    private $libraryRoot;

    /**
     * The root directory for website files.
     * Usually the same value as $_SERVER['DOCUMENT_ROOT'].
     *
     * @var string
     */
    private $documentRoot;

    /**
     * The full path to the measurement.php proxy file.
     *
     * @var string
     */
    private $measurementPhpFilePath;

    /**
     * The url path to the measurement.php proxy file.
     *
     * @var string
     */
    private $measurementPhpUrlPath;

    /**
     * Constructor.
     *
     * @param string $libraryRoot The root directory of the library.
     * @param string $documentRoot The root directory for the website files.
     */
    public function __construct(string $libraryRoot, string $documentRoot)
    {
        $this->documentRoot = $documentRoot;
        $this->libraryRoot = $libraryRoot;

        $this->measurementPhpFilePath =
            $this->libraryRoot . self::RELATIVE_MEASUREMENT_PHP;
    }

    /**
     * Get the fully qualified file path to the measurement.php proxy script.
     *
     * @return string Full file path to measurement.php.
     */
    public function getMeasurementPhpFilePath(): string
    {
        return $this->measurementPhpFilePath;
    }

    /**
     * Convert the measurement PHP file path to a valid URL.
     *
     * @throws InvalidMeasurementPathException
     *
     * @return string Url path to measurement.php.
     */
    public function getMeasurementPhpUrlPath(): string
    {
        if (!empty($this->measurementPhpUrlPath)) {
            return $this->measurementPhpUrlPath;
        }

        if (empty($this->documentRoot)) {
            throw new InvalidMeasurementPathException(
                'The website root is undefined or empty. ' .
                'It must be set to determine the request path for ' .
                'measurement.php. Check what value the server sets ' .
                '$_SERVER["DOCUMENT_ROOT"] to.'
            );
        }

        $measurementFile = explode(
            $this->documentRoot,
            $this->measurementPhpFilePath,
        )[1] ?? '';

        if (empty($measurementFile)) {
            throw new InvalidMeasurementPathException(
                'Cannot properly extract the relative website path for ' .
                "measurement.php from the website's root directory of " .
                "'{$this->documentRoot}' and the path of:" .
                $this->measurementPhpFilePath
            );
        }

        $urlPath = RequestHelper::sanitizePathForUrl($measurementFile);
        if (empty($urlPath)) {
            throw new InvalidMeasurementPathException(
                "Could not properly parse the relative site path of " .
                "'{$measurementFile}' into a valid url path.",
            );
        }

        $this->measurementPhpUrlPath = $urlPath;
        return $this->measurementPhpUrlPath;
    }


    /**
     * Initialize the Context class with the system defaults.
     *
     * @return Context The default Context.
     */
    public static function create(): Context
    {
        $libraryRoot = dirname(__DIR__, 2);
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        return new self($libraryRoot, $documentRoot);
    }
}
