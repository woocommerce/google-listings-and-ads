<?php

/**
 * Class Google\GoogleTagGatewayLibrary\Wordpress\UrlRewriter
 *
 * @pacakge   Google\GoogleTagGatewayLibrary\Wordpress
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Wordpress;

use Exception;
use Google\GoogleTagGatewayLibrary\Core\Context;

class UrlRewriter
{
    /**
     * The server context that GTG uses.
     *
     * @var Context
     */
    private $context;

    /**
     * GTG Wordpress settings.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Constructor.
     *
     * @param Settings $settings GTG Wordpress settings.
     * @param Context $context GTG server context.
     */
    public function __construct(
        Context $context,
        Settings $settings
    ) {
        $this->context = $context;
        $this->settings = $settings;
    }

    /**
     * Adds the GTG routing rules to send requests directly to measurement.php
     * using the Wordpress add_rewrite_rule function and the stored GTG
     * settings.
     *
     * @return bool TRUE if successfully added mod rewrite rules. FALSE if the
     * function failed to add the rewrite rules.
     */
    public function addModRewriteRules(): bool
    {
        if (!$this->settings->canUseModRewrite()) {
            return false;
        }

        $destination = '';
        try {
            $destination = $this->context->getMeasurementPhpUrlPath();
        } catch (Exception $e) {
            return false;
        }

        $requestUrl = $this->getRegexForMpath();
        $destination .= '?id=' . $this->settings->getTagId() . '&mpath=$1&s=$2';

        add_rewrite_rule($requestUrl, $destination, 'top');
        return true;
    }

    /**
     * Generate a regex path matcher for the saved measurement path.
     *
     * @return string A regex path matcher for the saved measurement path.
     */
    private function getRegexForMpath(): string
    {
        $regexMpath = preg_quote(
            // Wordpress rewrite rules will never contain the starting '/' and
            // the ending '/' should be gathered by the 2nd capturing group in
            // the regex below, if present, to ensure that the request path gets
            // set correctly. measurement.php should correctly resolve both a
            // missing or present path slash for the destination.
            trim($this->settings->getMeasurementPath(), '/'),
            // Properly escape the '/' character if it is still present in the
            // path.
            '/'
        );
        return '^(' . $regexMpath . ')(\/?.*)';
    }
}
