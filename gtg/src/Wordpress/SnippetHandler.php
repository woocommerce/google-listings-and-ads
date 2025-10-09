<?php

/**
 * Class Google\GoogleTagGatewayLibrary\Wordpress\SnippetHandler
 *
 * @pacakge   Google\GoogleTagGatewayLibrary\Wordpress
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Wordpress;

use Exception;
use Google\GoogleTagGatewayLibrary\GoogleTagGatewayHelper;

class SnippetHandler
{
    /**
     * GTG helper for generating script resources.
     *
     * @var GoogleTagGatewayHelper
     */
    private $helper;

    /**
     * Logging functions.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param GoogleTagGatewayHelper $helper GTG Helper.
     * @param Logger $logger Logging functions.
     */
    public function __construct(
        GoogleTagGatewayHelper $helper,
        Logger $logger
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * Inject a 1P Google tag onto the page using WP script injection.
     */
    public function inject()
    {
        try {
            $resources = $this->helper->createResources();
        } catch (Exception $e) {
            $this->logger->log(
                "An unexpected error occurred while injecting GTG snippet: " .
                $e->getMessage()
            );
            return;
        }

        wp_print_inline_script_tag($resources['topScript']);
        wp_print_inline_script_tag('', [
            'src' => $resources['src'],
            'async' => true,
        ]);
        wp_print_inline_script_tag($resources['script']);
    }
}
