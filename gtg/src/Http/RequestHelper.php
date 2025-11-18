<?php

/**
 * Class Google\GoogleTagGatewayLibrary\Http\RequestHelper
 *
 * @package   Google\GoogleTagGatewayLibrary
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Http;

/**
 * Isolates network requests and other methods like exit to inject into classes.
 */
class RequestHelper
{
    /**
     * Helper method to exit the script early and send back a status code.
     *
     * @param int $statusCode
     */
    public function invalidRequest(int $statusCode): void
    {
        http_response_code($statusCode);
        exit();
    }

    /**
     * Set the headers from a headers array.
     *
     * @param string[] $headers
     */
    public function setHeaders(array $headers): void
    {
        foreach ($headers as $header) {
            if (!empty($header)) {
                header($header);
            }
        }
    }

    /**
     * Sanitizes a path to a URL path.
     *
     * This function performs two critical actions:
     * 1. Extract ONLY the path component, discarding any scheme, host, port,
     *    user, pass, query, or fragment.
     *    Primary defense against Server-Side Request Forgery (SSRF).
     * 2. Normalize the path to resolve directory traversal segments like
     *    '.' and '..'.
     *    Prevents path traversal attacks.
     *
     * @param string $pathInput The raw path string.
     * @return string|false The sanitized and normalized URL path.
     */
    public static function sanitizePathForUrl(string $pathInput): string
    {
        if (empty($pathInput)) {
            return false;
        }
        // Normalize directory separators to forward slashes for Windows like directories.
        $path = str_replace('\\', '/', $pathInput);

        // 2. Normalize the path to resolve '..' and '.' segments.
        $parts = [];
        // Explode the path into segments. filter removes empty segments (e.g., from '//').
        $segments = explode('/', trim($path, '/'));

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '') {
                // Ignore current directory and empty segments.
                continue;
            }
            if ($segment === '..') {
                // Go up one level by removing the last part.
                if (array_pop($parts) === null) {
                    // If we try and traverse too far back, outside of the root
                    // directory, this is likely an invalid configuration so
                    // return false to have caller handle this error.
                    return false;
                }
            } else {
                // Add the segment to our clean path.
                $parts[] = rawurlencode($segment);
            }
        }

        // Rebuild the final path.
        $sanitizedPath = implode('/', $parts);

        return '/' . $sanitizedPath;
    }

    /**
     * Helper method to send requests depending on the PHP environment.
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     *
     * @return array{
     *      body: string,
     *      headers: string[],
     *      statusCode: int,
     * }
     */
    public function sendRequest(
        string $method,
        string $url,
        array $headers = [],
        ?string $body = null
    ): array {
        if ($this->isCurlInstalled()) {
            $response = $this->sendCurlRequest($method, $url, $headers, $body);
        } else {
            $response = $this->sendFileGetContents($method, $url, $headers, $body);
        }
        return $response;
    }

    /**
     * Send a request using curl.
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     *
     * @return array{
     *      body: string,
     *      headers: string[],
     *      statusCode: int,
     * }
     */
    protected function sendCurlRequest(
        string $method,
        string $url,
        array $headers = [],
        ?string $body = null
    ): array {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_URL, $url);

        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                break;
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $result = curl_exec($ch);

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headersString = substr($result, 0, $headerSize);
        $headers = explode("\r\n", $headersString);
        $headers = $this->normalizeHeaders($headers);

        $body = substr($result, $headerSize);

        curl_close($ch);

        return array(
            'body' => $body,
            'headers' => $headers,
            'statusCode' => $statusCode,
        );
    }

    /**
     * Send a request using file_get_contents.
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     *
     * @return array{
     *      body: string,
     *      headers: string[],
     *      statusCode: int,
     * }
     */
    protected function sendFileGetContents(
        string $method,
        string $url,
        array $headers = [],
        ?string $body = null
    ): array {
        $httpContext = [
            'method' => strtoupper($method),
            'follow_location' => 0,
            'max_redirects' => 0,
            'ignore_errors' => true,
        ];
        if (!empty($headers)) {
            $httpContext['header'] = implode("\r\n", $headers);
        }
        if (!empty($body)) {
            $httpContext['content'] = $body;
        }

        $streamContext = stream_context_create(['http' => $httpContext]);

        // Calling file_get_contents will set the variable $http_response_header
        // within the local scope.
        $result = file_get_contents($url, false, $streamContext);

        /** @var string[] $headers */
        $headers = $http_response_header ?? [];

        $statusCode = 200;
        if (!empty($headers)) {
            // The first element in the headers array will be the HTTP version
            // and status code used, parse out the status code and remove this
            // value from the headers.
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $statusHeader);
            $statusCode = intval($statusHeader[1]) ?? 200;
        }
        $headers = $this->normalizeHeaders($headers);

        return array(
            'body' => $result,
            'headers' => $headers,
            'statusCode' => $statusCode,
        );
    }

    protected function isCurlInstalled(): bool
    {
        return extension_loaded('curl');
    }

    /** @param string[] $headers */
    protected function normalizeHeaders(array $headers): array
    {
        if (empty($headers)) {
            return $headers;
        }

        // The first element in the headers array will be the HTTP version
        // and status code used, this value is not needed in the headers.
        array_shift($headers);
        return $headers;
    }

    /**
     * Takes a single URL query parameter which has not been encoded and
     * ensures its key & value are encoded.
     *
     * @param string $parameter Query parameter to encode.
     * @return string The new query parameter encoded.
     */
    public static function encodeQueryParameter(string $parameter): string
    {
        list($key, $value) = explode('=', $parameter, 2) + ['', ''];
        // We just manually encode to avoid any nuances that may occur as a
        // result of `http_build_query`. One such nuance is that
        // `http_build_query` will add an index to query parameters that
        // are repeated through an array. We would only be able to store
        // repeated values as an array as associative arrays cannot have the
        // same key multiple times. This makes `http_build_query`
        // undesirable as we should pass parameters through as they come in
        // and not modify them or change the key.
        $key = rawurlencode($key);
        $value = rawurlencode($value);

        return "{$key}={$value}";
    }
}
