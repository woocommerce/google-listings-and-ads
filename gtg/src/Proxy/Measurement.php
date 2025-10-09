<?php

/**
 * GoogleTagGatewayServing measurement request proxy file
 *
 * @package   Google\GoogleTagGatewayLibrary\Proxy
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Proxy;

use Google\GoogleTagGatewayLibrary\Http\RequestHelper;
use Google\GoogleTagGatewayLibrary\Http\ServerRequestContext;

/** Core measurement.php logic. */
final class Measurement
{
    private const TAG_ID_QUERY = '?id=';
    private const GEO_QUERY = '&geo=';
    private const PATH_QUERY = '&s=';
    private const FPS_PATH = 'PHP_GTG_REPLACE_PATH';

    /**
     * Reserved request headers that should not be sent as part of the
     * measurement request.
     *
     * @var array<string, bool>
     */
    private const RESERVED_HEADERS = [
        # PHP managed headers which will be auto populated by curl or file_get_contents.
        'HTTP_ACCEPT_ENCODING' => true,
        'HTTP_CONNECTION' => true,
        'HTTP_CONTENT_LENGTH' => true,
        'CONTENT_LENGTH' => true,
        'HTTP_EXPECT' => true,
        'HTTP_HOST' => true,
        'HTTP_TRANSFER_ENCODING' => true,
        # Sensitive headers to exclude from all requests.
        'HTTP_AUTHORIZATION' => true,
        'HTTP_PROXY_AUTHORIZATION' => true,
        'HTTP_X_API_KEY' => true,
    ];

    /**
     * Request helper.
     *
     * @var RequestHelper
     */
    private RequestHelper $helper;

    /**
     * Server request context.
     *
     * @var ServerRequestContext
     */
    private ServerRequestContext $serverRequest;

    /**
     * Create the measurement request handler.
     *
     * @param RequestHelper $helper
     * @param ServerRequestContext $serverReqeust
     */
    public function __construct(
        RequestHelper $helper,
        ServerRequestContext $serverRequest
    ) {
        $this->helper = $helper;
        $this->serverRequest = $serverRequest;
    }

    /** Run the measurement logic. */
    public function run()
    {
        $redirectorFile = $this->serverRequest->getRedirectorFile();
        if (empty($redirectorFile)) {
            $this->helper->invalidRequest(500);
            return "";
        }

        $tagId = $this->serverRequest->getTagId();
        $path = $this->serverRequest->getDestination();
        $geo = $this->serverRequest->getGeoParam();
        $mpath = $this->serverRequest->getMeasurementPath();

        if (empty($tagId) || empty($path)) {
            $this->helper->invalidRequest(400);
            return "";
        }

        $useMpath = empty($mpath) ? self::FPS_PATH : $mpath;

        $fpsUrl = 'https://' . $tagId . '.fps.goog/' . $useMpath . $path;

        $requestHeaders =
            $this->serverRequest->getHeaders(self::RESERVED_HEADERS);

        $method = $this->serverRequest->getMethod();
        $body = $this->serverRequest->getBody();
        $response = $this->helper->sendRequest(
            $method,
            $fpsUrl,
            $requestHeaders,
            $body
        );

        if ($useMpath === self::FPS_PATH) {
            $substitutionMpath = $redirectorFile . self::TAG_ID_QUERY . $tagId;
            if (!empty($geo)) {
                $substitutionMpath .= self::GEO_QUERY . $geo;
            }
            $substitutionMpath .= self::PATH_QUERY;

            if (self::isScriptResponse($response['headers'])) {
                $response['body'] = str_replace(
                    '/' . self::FPS_PATH . '/',
                    $substitutionMpath,
                    $response['body']
                );
            } elseif (self::isRedirectResponse($response['statusCode']) && !empty($response['headers'])) {
                foreach ($response['headers'] as $refKey => $header) {
                    // Ensure we are only processing strings.
                    if (!is_string($header)) {
                        continue;
                    }

                    $headerParts = explode(':', $response['headers'][$refKey], 2);
                    if (count($headerParts) !== 2) {
                        continue;
                    }
                    $key = trim($headerParts[0]);
                    $value = trim($headerParts[1]);
                    if (strtolower($key) !== 'location') {
                        continue;
                    }

                    $newValue = str_replace(
                        '/' . self::FPS_PATH,
                        $substitutionMpath,
                        $value
                    );
                    $response['headers'][$refKey] = "{$key}: {$newValue}";
                    break;
                }
            }
        }
        return $response;
    }

    /**
     * @param string[] $headers
     */
    private static function isScriptResponse(array $headers): bool
    {
        if (empty($headers)) {
            return false;
        }

        foreach ($headers as $header) {
            if (empty($header)) {
                continue;
            }

            $normalizedHeader = strtolower(str_replace(' ', '', $header));
            if (strpos($normalizedHeader, 'content-type:application/javascript') === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the response is a redirect response.
     * @param int $statusCode
     */
    private static function isRedirectResponse(int $statusCode): bool
    {
        return $statusCode >= 300 && $statusCode < 400;
    }
}
