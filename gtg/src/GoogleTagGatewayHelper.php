<?php

/**
 * Class Google\GoogleTagGatewayLibrary\GoogleTagGatewayHelper
 *
 * @package   Google\GoogleTagGatewayLibrary
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary;

use Exception;
use Google\GoogleTagGatewayLibrary\Exceptions\InvalidContainerIdException;
use Google\GoogleTagGatewayLibrary\Exceptions\InvalidMeasurementPathException;
use Google\GoogleTagGatewayLibrary\Http\RequestHelper;

final class GoogleTagGatewayHelper
{
    private const KNOWN_GEO_HEADERS = [
        'HTTP_X_FORWARDED_COUNTRY',
        'HTTP_X_FORWARDED_COUNTRYREGION',
        'HTTP_CF_IPCOUNTRY',
        'HTTP_X_CFIPCOUNTRYREGION',
        'HTTP_CF_IPCOUNTRYREGION',
        'HTTP_X_GCLB_COUNTRY',
        'HTTP_X_AKAMAI_EDGESCAPE',
        'HTTP_X_AZURE_COUNTRY',
        'HTTP_CLOUDFRONT_VIEWER_COUNTRY',
    ];

    private const MEASUREMENT_PATH_MAX_LENGTH = 100;

    /** @var RequestHelper */
    private $requestHelper;
    private $defaultPhpRedirectorFile = __DIR__ . '/../dist/measurement.php';
    private $containerId = '';
    private $gtgUrl = '';
    private $queryStart = '?';
    private $customMpath = '';
    private $geoFunction = '';
    private $devId = 'dYmZiZj';

  /**
   * Create a helper to serve a first party script on the page.
   *
   * @param string $containerId
   * @param array{
   * geoFunction?: string,
   * mpath?: string
   * } $options Optional parameters that the function accepts:
   *   - `geoFunction`: The name of a javascript function on page that will
   *                  retrieve geo information to pass along.
   *   - `mpath`: Overwrite the default measurement.php file location and route
   *              all GTG requests to a new path.
   */
    public function __construct($containerId, $options = [])
    {
        $this->containerId = $containerId;

        if (!empty($options['geoFunction'])) {
            $this->geoFunction = $options['geoFunction'];
        }

        if (!empty($options['mpath'])) {
            $this->customMpath = $options['mpath'];
        }

        $this->requestHelper = new RequestHelper();
    }

    /**
     * Creates the script tag resources for the current container.
     *
     * Returns a dictionary containing the src attribute for a script
     * tag and a JS snippet to include within a script tag. If `src`
     * is missing no src attribute needs to be added to the script tag.
     *
     * @throws InvalidContainerIdException
     * @throws InvalidMeasurementPathException
     *
     * @return array{
     *      'src': string,
     *      'script': string,
     *      'topScript': string,
     * } The different scripts needed to inject onto a page.
     *      - `src`: A src attribute to be included on one script tag.
     *        This tag should also include the async attribute with it.
     *      - `script`: The main body of a script tag.
     *      - `topScript`: The main body of another script tag. This should be
     *        included on the page before any of the other scripts.
     */
    public function createResources(): array
    {
        $this->initializeGtgValues();

        if ($this->shouldFetchGeo()) {
            return [
                "src" => '',
                "script" => $this->createScriptWithGeo(),
                "topScript" => $this->createFpmScript(),
            ];
        } else {
            return [
                "src" => $this->gtgUrl,
                "script" => $this->createScript(),
                "topScript" => $this->createFpmScript(),
            ];
        }
    }

    /**
     * Forcibly remove the custom measurement path set on initialization.
     */
    public function removeMeasurementPath(): void
    {
        $this->customMpath = '';
    }

    /**
     * Perform a health check to make sure the measurement.php script is
     * reachable and properly forward requests.
     *
     * @return array{
     *      'status': boolean,
     *      'errorMessage'?: string,
     * } Results of a health check to measurement.php. If `status` is FALSE than
     * `errorMessage` will contain a string with why the check failed.
     * `errorMessage` is otherwise not set when `status` is TRUE.
     */
    public function healthCheck(): array
    {
        try {
            $this->initializeGtgValues();
        } catch (Exception $e) {
            return [
                'status' => false,
                'errorMessage' => $e->getMessage(),
            ];
        }

        if (
            empty($_SERVER['SCRIPT_NAME']) ||
            empty($_SERVER['SERVER_NAME'])
        ) {
            return array(
                'status' => false,
                'errorMessage' => 'Missing required $_SERVER variables. ' .
                    'Required $_SERVER variables: SCRIPT_NAME, SERVER_NAME',
            );
        }

        $httpScheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https://' : 'http://';
        $healthCheckUrl = $httpScheme . $_SERVER['SERVER_NAME'] . $this->gtgUrl . $this->queryStart . 's=/healthy';

        $response = $this->requestHelper->sendRequest('GET', $healthCheckUrl);

        $results = array(
            "status" => (
                $response['body'] === 'ok' &&
                $response['statusCode'] === 200
            ),
        );

        if (!$results['status']) {
            $results['errorMessage'] =
               'The measurement script health check failed for an unknown reason.';
        }

        return $results;
    }

    /**
     * Set the requestHelper for handling requests within the
     * GoogleTagGatewayHelper during testing.
     *
     * @interal
     *
     * @param RequestHelper $requestHelper
     */
    public function setRequestHelper($requestHelper)
    {
        $this->requestHelper = $requestHelper;
    }

    /**
     * Set the default PHP redirector file location for testing.
     *
     * @interal
     *
     * @param string $newFile
     */
    public function setDefaultPhpRedirectorFile($newFile)
    {
        $this->defaultPhpRedirectorFile = $newFile;
    }

    /**
     * Test whether the Tag ID passed in is a valid ID.
     *
     * @return bool TRUE if the tag ID is valid. FALSE otherwise.
     */
    public static function validateTagId($tagId): bool
    {
        return preg_match('/^[a-zA-Z]+-[a-zA-Z0-9]+$/', $tagId);
    }

    private function createScript()
    {
        return "
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('set', 'developer_id.{$this->devId}', true);
            gtag('js', new Date());
            gtag('config', '{$this->containerId}');
        ";
    }

    private function createScriptWithGeo()
    {
        $scriptFunction = [
            'parameters' => 'd, s, i',
            'arguments' => "document, 'script', '{$this->containerId}'",
            'geoTimeout' => 500,
        ];

        return "
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('set', 'developer_id.{$this->devId}', true);
            gtag('js', new Date());

            (function ({$scriptFunction['parameters']}) {
              gtag('config', i);
              function create(gr) {
                gr = (gr || '').replace(/[^A-Za-z0-9\-]/g, '');
                var f = d.getElementsByTagName(s)[0],
                  j = d.createElement(s),
                  gp = gr ? '{$this->queryStart}geo=' + gr : '';
                j.async = true;
                j.src = '{$this->gtgUrl}' + gp;
                f.parentNode.insertBefore(j, f);
              }

              Promise.race([
                new Promise((resolve) => resolve({$this->geoFunction}())),
                new Promise((_, reject) =>
                  setTimeout(() => reject(new Error('timeout')), {$scriptFunction['geoTimeout']}))
              ]).then(create)
                .catch(() => create(''));
            })({$scriptFunction['arguments']});
        ";
    }

    private function createFpmScript()
    {
        return "
            (function(w,i,g){w[g]=w[g]||[];if(typeof w[g].push=='function')w[g].push.apply(w[g], i)})
            (window,['{$this->containerId}'],'google_tags_first_party');
        ";
    }

    private function shouldFetchGeo()
    {
        return !empty($this->geoFunction) && !$this->hasKnownGeoHeader();
    }

    /** Check if the request contains any known geo headers. */
    private function hasKnownGeoHeader()
    {
        foreach (self::KNOWN_GEO_HEADERS as $geoHeader) {
            if (isset($_SERVER[$geoHeader])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convert the measurement PHP file path to a valid URL.
     *
     * @throws InvalidMeasurementPathException
     */
    private function getMeasurementPhpFileAsUrl()
    {
        if (empty($_SERVER['DOCUMENT_ROOT'])) {
            throw new InvalidMeasurementPathException(
                '$_SERVER["DOCUMENT_ROOT"] is undefined or empty. ' .
                'It must be set for determining the request path for ' .
                'measurement.php.'
            );
        }

        $phpFile = $this->defaultPhpRedirectorFile;
        $measurementFile = explode($_SERVER['DOCUMENT_ROOT'], $phpFile)[1] ?? '';
        if (empty($measurementFile)) {
            throw new InvalidMeasurementPathException(
                'Could not properly parse the measurement path for ' .
                'measurement.php from the root directory of ' .
                "'{$_SERVER['DOCUMENT_ROOT']}' and the path of: {$phpFile}"
            );
        }

        $urlPath = RequestHelper::sanitizePathForUrl($measurementFile);
        if (empty($urlPath)) {
            throw new InvalidMeasurementPathException(
                "Could not properly parse the measurement path " .
                "'{$measurementFile}' into a valid url path."
            );
        }
        return $urlPath;
    }

    /**
     * Format and validate the measurement path.
     *
     * @param string $mpath
     */
    private function formatMpath(string $mpath): string
    {
        $newPath = self::addMpathSlashes($mpath);

        $validMpath = self::validateMpath($newPath);
        if (!$validMpath) {
            throw new InvalidMeasurementPathException(
                "The measurement path '{$newPath}' is not a valid url path." .
                "The measurement path must only contain alpha-numeric " .
                "characters, '/', no consecutive '/', and be less than " .
                self::MEASUREMENT_PATH_MAX_LENGTH . " characters long.",
            );
        }

        return $newPath;
    }

    /**
     * Add missing leading or trailing slash in mpath.
     *
     * @param string $mpath
     */
    private static function addMpathSlashes(string $mpath): string
    {
        $newPath = '';
        if (substr($mpath, 0, 1) !== '/') {
            $newPath .= '/';
        }
        $newPath .= $mpath;
        if (substr($mpath, -1) !== '/') {
            $newPath .= '/';
        }
        return $newPath;
    }

    public static function validateMpath(string $mpath): bool
    {
        // This will ensure the mpath has leading and trailing slashes when
        // calculating its length below, which should be taken into account.
        $formattedMpath = self::addMpathSlashes($mpath);

        // Ensure the path is not empty.
        if (empty($formattedMpath)) {
            return false;
        }

        // Check for invalid characters. Only alphanumeric and '/' are allowed.
        if (preg_match('/[^a-zA-Z0-9\/]/', $formattedMpath)) {
            return false;
        }

        // Check for consecutive slashes.
        if (strpos($formattedMpath, '//') !== false) {
            return false;
        }

        // Ensure the path is not just a single '/'.
        if ($formattedMpath === '/') {
            return false;
        }

        // Ensure the path is not too long.
        if (strlen($formattedMpath) > self::MEASUREMENT_PATH_MAX_LENGTH) {
            return false;
        }

        return true;
    }


    /**
     * Initialize GTG class variables.
     *
     * @throws InvalidContainerIdException
     * @throws InvalidMeasurementPathException
     */
    private function initializeGtgValues(): void
    {
        if (!self::validateTagId($this->containerId)) {
            throw new InvalidContainerIdException($this->containerId);
        }

        if (empty($this->customMpath)) {
            $this->gtgUrl = $this->getMeasurementPhpFileAsUrl() .
                '?id=' . $this->containerId;
            $this->queryStart = '&';
        } else {
            $this->queryStart = '?';
            $this->gtgUrl = $this->formatMpath($this->customMpath);
        }
    }
}
