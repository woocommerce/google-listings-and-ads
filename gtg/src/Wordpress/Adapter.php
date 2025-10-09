<?php

/**
 * Class Google\GoogleTagGatewayLibrary\Wordpress\Adapter
 *
 * @pacakge   Google\GoogleTagGatewayLibrary\Wordpress
 * @copyright 2024 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

namespace Google\GoogleTagGatewayLibrary\Wordpress;

use Google\GoogleTagGatewayLibrary\Core\Context;
use Google\GoogleTagGatewayLibrary\GoogleTagGatewayHelper;

final class Adapter
{
    /**
     * Characters used for generating a random measurement path.
     *
     * @var string
     */
    private const RAND_MPATH_CHARACTER_SET =
        'abcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * The minimum length to use when generating a random measurement path.
     */
    private const RAND_MPATH_MIN_LENGTH = 4;

    /**
     * The maximum length to use when generating a random measurement path.
     */
    private const RAND_MPATH_MAX_LENGTH = 10;

    /**
     * Helper to inject script.
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
     * GTG Wordpress settings.
     *
     * @var Settings
     */
    private $settings;

    /**
     * GTG url rewrite rules.
     *
     * @var UrlRewriter
     */
    private $urlRewriter;

    /**
     * GTG snippet injection handler.
     *
     * @var SnippetHandler
     */
    private $snippet;

    /**
     * Constructor. This should not be invoked directly instead prefer the
     * create() function.
     *
     * @see create
     *
     * @param GoogleTagGatewayHelper $helper GTG helper.
     * @param Logger $logger Logging functions.
     * @param Settings $settings GTG Wordpress settings.
     * @param SnippetHandler $snippet GTG snippet injection handler.
     * @param UrlRewriter $urlRewriter GTG url rewrite rules.
     */
    public function __construct(
        GoogleTagGatewayHelper $helper,
        Logger $logger,
        Settings $settings,
        SnippetHandler $snippet,
        UrlRewriter $urlRewriter
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->settings = $settings;
        $this->snippet = $snippet;
        $this->urlRewriter = $urlRewriter;
    }

    /**
     * Initialize GTG on the page.
     *
     * This will add rewrite rules into memory as well as inject 1P scripts
     * onto the page.
     */
    public function initialize()
    {
        if (empty($this->settings->getTagId())) {
            $this->logger->log(
                'GoogleTagGateway Tag ID must be set when using GTG.'
            );
            return;
        }

        if (empty($this->settings->getMeasurementPath())) {
            $this->logger->log(
                'GoogleTagGateway should use a measurement path to ensure ' .
                'full functionality.'
            );
        } else {
            $this->addUrlRules();
        }

        $this->addScriptInjection();
    }

    /**
     * Update and save GTG values.
     *
     * Note this function should not be called frequently as it calls
     * `flush_rewrite_rules` on updates which will impact site performance if
     * called excessively.
     *
     * @param array{
     *      'tagId'?: string,
     *      'measurementPath'?: string,
     * } $values Associative array of GTG values to update.
     *      - `tagId`: The primary tag ID that will be loaded on page.
     *      - `measurementPath`: A custom path to route measurement requests to.
     *        This will route the given path to a measurement.php proxy script
     *        file. If left blank and none is set when calling this method a
     *        random alpha-numeric path will be set for you.
     */
    public function update($values = []): void
    {
        $tagIdUpdated = false;
        if (isset($values['tagId'])) {
            $tagIdUpdated = $this->settings->updateTagId($values['tagId']);
        }

        $mpathUpdated = false;
        if (isset($values['measurementPath'])) {
            $mpathUpdated =
                $this->settings
                     ->updateMeasurementPath($values['measurementPath']);
        }

        if (empty($this->settings->getMeasurementPath())) {
            $mpathUpdated =
                $this->settings
                     ->updateMeasurementPath($this->generateRandomMpath());
        }

        if ($mpathUpdated || $tagIdUpdated) {
            // Attempt to set the mod rewrite rules and if that fails attempt
            // to add the permalink rules.
            $urlRulesSet = $this->urlRewriter->addModRewriteRules();

            // Only when rules were actually set should we call
            // `flush_rewrite_rules()`
            if ($urlRulesSet) {
                flush_rewrite_rules();
            }
        }
    }

    /**
     * Generate a random alpha-numeric measurement path.
     *
     * @return string Randomized measurement path.
     */
    protected function generateRandomMpath(): string
    {
        $mpathLength = mt_rand(
            self::RAND_MPATH_MIN_LENGTH,
            self::RAND_MPATH_MAX_LENGTH,
        );

        $randCharsLength = strlen(self::RAND_MPATH_CHARACTER_SET);
        $randomMpath = '';

        for ($i = 0; $i < $mpathLength; $i++) {
            $randomIndex = random_int(0, $randCharsLength - 1);
            $randomMpath .= self::RAND_MPATH_CHARACTER_SET[$randomIndex];
        }

        return $randomMpath;
    }

    /**
     * Add WP actions to register rewrite rules to route measurement.php to a
     * configured URL path.
     */
    protected function addUrlRules(): void
    {
        if ($this->settings->canUseModRewrite()) {
            add_action(
                'init', /* hook_name */
                fn() => $this->urlRewriter->addModRewriteRules(), /* callback */
            );
        } else {
            $this->helper->removeMeasurementPath();
            $this->logger->log(
                'URL Rewriting on Wordpress server is disabled or not ' .
                'supported. Please enable permalinks in the ' .
                '`WP Admin > Settings > Permalinks` page and ensure that ' .
                'mod_rewrite is supported on your server to ensure ' .
                'Google Tag Gateway will function properly.',
            );
        }
    }

    /**
     * Add WP actions to inject 1P scripts.
     */
    protected function addScriptInjection(): void
    {
        add_action(
            'wp_head', /* hook_name */
            fn() => $this->snippet->inject(), /* callback */
            0, /* priority */
        );
    }

    /**
     * Initialize the GoogleTagGatewayHelper using the Wordpress settings.
     *
     * @param Settings $settings The Wordpress settings for GTG.
     * @return GoogleTagGatewayHelper An helper instance to generate 1P scripts.
     */
    protected static function initializeHelper(Settings $settings): GoogleTagGatewayHelper
    {
        $tagId = $settings->getTagId();
        $mpath = $settings->getMeasurementPath();

        $helperOptions = [];
        if (!empty($mpath) && $settings->isRewritingEnabled()) {
            $helperOptions['mpath'] = $mpath;
        }

        return new GoogleTagGatewayHelper($tagId, $helperOptions);
    }

    /**
     * Create a new instance of the Wordpress GTG Adapter class.
     *
     * @return Adapter Wordpress GTG Adapter.
     */
    public static function create(): Adapter
    {
        $logger = new Logger();
        $settings = new Settings($logger);

        $helper = self::initializeHelper($settings);
        $snippet = new SnippetHandler($helper, $logger);

        $context =  Context::create();
        $urlRewriter = new UrlRewriter($context, $settings);

        return new self($helper, $logger, $settings, $snippet, $urlRewriter);
    }
}
