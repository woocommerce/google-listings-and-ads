<?php

/**
 * Class Google\GoogleTagGatewayLibrary\Wordpress\Settings
 *
 * @pacakge   Google\GoogleTagGatewayLibrary\Wordpress
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Wordpress;

use Google\GoogleTagGatewayLibrary\GoogleTagGatewayHelper;

class Settings
{
    /**
     * Tag ID key for WP storage.
     *
     * @var string
     */
    private const TAG_ID_KEY = 'googletaggateway_tag_id';

    /**
     * Measurement path key for WP storage.
     *
     * @var string
     */
    private const MPATH_KEY = 'googletaggateway_measurement_path';

    /**
     * Logging functions.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Saved tag ID for re-use.
     *
     * @var string|null
     */
    private $tagId;

    /**
     * Saved measurement path for re-use.
     *
     * @var string|null
     */
    private $mpath;

    /**
     * Saved value of mod-rewrite enabled for re-use.
     *
     * @var bool|null
     */
    private $modRewriteEnabled;

    /**
     * Saved value of permalinks enabled for re-use.
     *
     * @var bool|null
     */
    private $permalinksEnabled;

    /**
     * Constructor.
     *
     * @param Logger $logger Logging functions.
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Checks whether or not url rewriting is enabled in general.
     *
     * @return bool TRUE if any form of url rewriting is enabled, otherwise FALSE.
     */
    public function isRewritingEnabled()
    {
        return $this->canUseModRewrite();
    }

    /**
     * Checks whether or not mod rewrite is enabled and usable on the server.
     *
     * @return bool TRUE if mod rewrite is enabled and usable, otherwise FALSE.
     */
    public function canUseModRewrite()
    {
        return $this->checkModRewrite() && $this->checkPermalinks();
    }

    /**
     * Checks whether or not the mod rewrite value has already been looked up
     * if so use the cached value other grab the current value and return it.
     *
     * @return bool TRUE if mod rewrite is enabled, otherwise FALSE.
     */
    protected function checkModRewrite()
    {
        if (!isset($this->modRewriteEnabled)) {
            $this->modRewriteEnabled = apache_mod_loaded('mod_rewrite', false);
        }
        return $this->modRewriteEnabled;
    }

    /**
     * Checks whether or not the permalinks value has already been looked up
     * if so use the cached value other grab the current value and return it.
     *
     * @return bool TRUE if permalinks are enabled, otherwise FALSE.
     */
    protected function checkPermalinks()
    {
        if (!isset($this->permalinksEnabled)) {
            $this->permalinksEnabled = (bool) get_option('permalink_structure');
        }
        return $this->permalinksEnabled;
    }

    /**
     * Fetches the primary Tag ID that GTG is configured with.
     *
     * @return string The primary Tag ID
     */
    public function getTagId()
    {
        if (!isset($this->tagId)) {
            $this->tagId = get_option(self::TAG_ID_KEY, '');
        }

        return $this->tagId;
    }

    /**
     * Fetches the measurement path that GTG is configured with.
     *
     * @return string The measurement path for GTG
     */
    public function getMeasurementPath()
    {
        if (!isset($this->mpath)) {
            $this->mpath = get_option(self::MPATH_KEY, '');
        }

        return $this->mpath;
    }

    /**
     * Updates the Tag ID value used in GTG.
     *
     * @return bool True on success, false on failure.
     */
    public function updateTagId($tagId)
    {
        if (!GoogleTagGatewayHelper::validateTagId($tagId)) {
            $this->logger->log(
                "Invalid tag ID attempted to be stored: " . $tagId
            );
            return false;
        }


        $updateSuccess = update_option(self::TAG_ID_KEY, $tagId);
        if ($updateSuccess) {
            $this->tagId = $tagId;
        }
        return $updateSuccess;
    }

    /**
     * Updates the measurement path value used in GTG.
     *
     * @return bool True on success, false on failure.
     */
    public function updateMeasurementPath($mpath)
    {
        if (!GoogleTagGatewayHelper::validateMpath($mpath)) {
            $this->logger->log(
                "Invalid measurement path attempted to be stored: " . $mpath
            );
            return false;
        }

        $updateSuccess = update_option(self::MPATH_KEY, $mpath);
        if ($updateSuccess) {
            $this->mpath = $mpath;
        }
        return $updateSuccess;
    }
}
