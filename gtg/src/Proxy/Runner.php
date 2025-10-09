<?php

/**
 * Runner to execute the proxy request.
 *
 * @package   Google\GoogleTagGatewayLibrary\Proxy
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Proxy;

use Google\GoogleTagGatewayLibrary\Http\RequestHelper;
use Google\GoogleTagGatewayLibrary\Http\ServerRequestContext;

/** Runner class to execute the proxy request. */
final class Runner
{
    /**
     * Request helper functions.
     *
     * @var RequestHelper
     */
    private RequestHelper $helper;

    /**
     * Measurement request helper.
     *
     * @var Measurement
     */
    private Measurement $measurement;

    /**
     * Constructor.
     *
     * @param RequestHelper $helper
     */
    public function __construct(
        RequestHelper $helper,
        Measurement $measurement
    ) {
        $this->helper = $helper;
        $this->measurement = $measurement;
    }

    /** Run the core logic for forwarding traffic. */
    public function run(): void
    {
        $response = $this->measurement->run();

        $this->helper->setHeaders($response['headers']);
        http_response_code($response['statusCode']);
        echo $response['body'];
    }

    /** Create an instance of the runner with the system defaults. */
    public static function create()
    {
        $helper = new RequestHelper();
        $context = ServerRequestContext::create();
        $measurement = new Measurement($helper, $context);

        return new self($helper, $measurement);
    }
}
