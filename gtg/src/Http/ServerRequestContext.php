<?php

/**
 * Class Google\GoogleTagGatewayLibrary\Http\ServerRequestContext;
 *
 * @package   Google\GoogleTagGatewayLibrary\Http
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Http;

/** Request context populated with common server set values. */
final class ServerRequestContext
{
    /**
     * Server set assosiative array. Noramlly the same as $_SERVER.
     *
     * @var array
     */
    private $serverParams;

    /**
     * Associative array of query parameters. Normally the same as $_GET
     *
     * @var array
     */
    private $queryParams;

    /**
     * The current server request's body.
     *
     * @var string
     */
    private $requestBody;

    /**
     * Constructor
     *
     * @param array $serverParams
     * @param array $queryParams
     * @param string $requestBody
     */
    public function __construct(
        array $serverParams,
        array $queryParams,
        string $requestBody
    ) {
        $this->serverParams = $serverParams;
        $this->queryParams = $queryParams;
        $this->requestBody = $requestBody;
    }

    /** Create an instance with the system defaults. */
    public static function create()
    {
        $body = file_get_contents("php://input") ?? '';
        return new self($_SERVER, $_GET, $body);
    }

    /**
     * Fetch the current request's request body.
     *
     * @return string The current request body.
     */
    public function getBody(): string
    {
        return $this->requestBody ?? '';
    }

    public function getRedirectorFile()
    {
        $redirectorFile = $this->serverParams['SCRIPT_NAME'] ?? '';
        if (empty($redirectorFile)) {
            return '';
        }

        return RequestHelper::sanitizePathForUrl($redirectorFile);
    }

    /**
     * Get headers from the current request as an array of strings.
     * Similar to how you set headers using the `headers` function.
     *
     * @param array $filterHeaders Filter out headers from the return value.
     */
    public function getHeaders(array $filterHeaders = []): array
    {
        $headers = [];

        // Extra headers not prefixed with `HTTP_`
        $extra_headers = [
            "CONTENT_TYPE" => 'content-type',
            "CONTENT_LENGTH" => 'content-length',
            "CONTENT_MD5" => 'content-md5',
        ];

        foreach ($this->serverParams as $key => $value) {
            # Skip reserved headers
            if (isset($filterHeaders[$key])) {
                continue;
            }

            # All PHP request headers are available under the $_SERVER variable
            # and have a key prefixed with `HTTP_` according to:
            # https://www.php.net/manual/en/reserved.variables.server.php#refsect1-reserved.variables.server-description
            $headerKey = '';
            if (substr($key, 0, 5) === 'HTTP_') {
                # PHP defaults to every header key being all capitalized.
                # Format header key as lowercase with `-` as word separator.
                # For example: cache-control
                $headerKey = strtolower(str_replace('_', '-', substr($key, 5)));
            } elseif (isset($extra_headers[$key])) {
                $headerKey = $extra_headers[$key];
            }

            if (empty($headerKey) || empty($value)) {
                continue;
            }

            $headers[] = "$headerKey: $value";
        }

        // Add extra x-forwarded-for if remote address is present.
        if (isset($this->serverParams['REMOTE_ADDR'])) {
            $headers[] = "x-forwarded-for: {$this->serverParams['REMOTE_ADDR']}";
        }

        // Add extra geo if present in the query parameters.
        $geo = $this->getGeoParam();
        if (!empty($geo)) {
            $headers[] = "x-forwarded-countryregion: {$geo}";
        }

        return $headers;
    }

    /**
     * Get the request method made for the current request.
     *
     * @return string
     */
    public function getMethod()
    {
        return @$this->serverParams['REQUEST_METHOD'] ?: 'GET';
    }

    /** Get and validate the geo parameter from the request. */
    public function getGeoParam()
    {
        $geo = $this->queryParams['geo'] ?? '';

        // Basic geo validation
        if (!preg_match('/^[A-Za-z0-9-]+$/', $geo)) {
            return '';
        }
        return $geo;
    }

    /** Get the tag id query parmeter from the request.  */
    public function getTagId()
    {
        $tagId = $this->queryParams['id'] ?? '';

        // Validate tagId
        if (!preg_match('/^[A-Za-z0-9-]+$/', $tagId)) {
            return '';
        }

        return $tagId;
    }

    /** Get the destination query parmeter from the request.  */
    public function getDestination()
    {
        $path = $this->queryParams['s'] ?? '';

        // When measurement path is present it might accidentally pass an empty
        // path character depending on how the url rules are processed so as a
        // safety when path is empty we should assume that it is a request to
        // the root.
        if (empty($path)) {
            $path = '/';
        }

        // Remove reserved query parameters from the query string
        $params = $this->queryParams;
        unset($params['id'], $params['s'], $params['geo'], $params['mpath']);

        $containsQueryParameters = strpos($path, '?') !== false;
        if ($containsQueryParameters) {
            list($path, $query) = explode('?', $path, 2);
            $path .= '?' . RequestHelper::encodeQueryString($query);
        }

        if (!empty($params)) {
            $paramSeparator = $containsQueryParameters ? '&' : '?';
            $path .= $paramSeparator .
                http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        return $path;
    }

    /**Get the measurement path query parmaeter from the request.  */
    public function getMeasurementPath()
    {
        return $this->queryParams['mpath'] ?? '';
    }
}
