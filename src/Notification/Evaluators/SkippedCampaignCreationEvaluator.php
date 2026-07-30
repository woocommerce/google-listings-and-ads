<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaign;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\CampaignType;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\CachedNotificationEvaluatorTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\InvalidatableNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\ServiceBasedMerchantState;

defined( 'ABSPATH' ) || exit;

/**
 * Class SkippedCampaignCreationEvaluator
 *
 * Fires when the merchant finished onboarding but skipped campaign creation: onboarding
 * is complete and the account has no enabled Performance Max campaigns — including any
 * created outside the onboarding flow.
 *
 * For retail (shopping) merchants, completing the Ads setup implies a campaign was created,
 * so a completed Ads setup suppresses this notification. Service-based (ads-only) merchants
 * always complete their Ads setup during onboarding — even when they skip campaign creation —
 * so that shortcut does not apply to them; their "skipped" state is determined solely by the
 * absence of an enabled Performance Max campaign.
 *
 * A paused-only Performance Max campaign does not suppress this notification; that case
 * is surfaced by the separate paused-campaign notification instead.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class SkippedCampaignCreationEvaluator implements InvalidatableNotificationEvaluatorInterface, AdsAwareInterface, OptionsAwareInterface, Service {

	use AdsAwareTrait;
	use CachedNotificationEvaluatorTrait;
	use OptionsAwareTrait;

	/** @var AdsCampaign */
	private $ads_campaign;

	/** @var OnboardingCompleted */
	private $onboarding_completed;

	/** @var ServiceBasedMerchantState */
	private $service_based_merchant_state;

	/**
	 * SkippedCampaignCreationEvaluator constructor.
	 *
	 * @param AdsCampaign               $ads_campaign
	 * @param OnboardingCompleted       $onboarding_completed
	 * @param ServiceBasedMerchantState $service_based_merchant_state
	 */
	public function __construct( AdsCampaign $ads_campaign, OnboardingCompleted $onboarding_completed, ServiceBasedMerchantState $service_based_merchant_state ) {
		$this->ads_campaign                 = $ads_campaign;
		$this->onboarding_completed         = $onboarding_completed;
		$this->service_based_merchant_state = $service_based_merchant_state;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'skipped-campaign-creation';
	}

	/**
	 * Evaluate whether the notification condition is met.
	 *
	 * @return bool
	 */
	protected function evaluate_condition(): bool {
		// Only merchants who finished onboarding are candidates for "skipped campaign".
		if ( ! $this->onboarding_completed->is_onboarding_complete() ) {
			return false;
		}

		// For retail (shopping) merchants, a completed Ads setup implies a campaign was
		// created, so the merchant did not skip campaign creation. This shortcut does not
		// apply to service-based merchants: their ads-only onboarding always completes Ads
		// setup, even when they skip campaign creation, so whether they skipped is
		// determined solely by the absence of an enabled campaign checked below.
		if ( ! $this->service_based_merchant_state->is_service_based_merchant()
			&& $this->ads_service->is_setup_complete() ) {
			return false;
		}

		// No Ads account connected — either never connected, or disconnected again in
		// settings after being set up. Neither is "skipped campaign creation", and
		// AdsCampaign::get_campaigns() requires a non-zero Ads ID and throws otherwise.
		// (Deliberately not using AdsService::connected_account() here — it also requires
		// every account-creation step but "billing" to be marked done, which isn't true
		// for accounts connected via some paths, e.g. linking an existing Ads account.)
		if ( ! $this->options->get_ads_id() ) {
			return false;
		}

		try {
			$campaigns = $this->ads_campaign->get_campaigns( true, false );

			// Any *enabled* Performance Max campaign (including ones created outside
			// the onboarding flow) means the merchant did not skip campaigns. A
			// paused-only PMax campaign does not suppress this notification — that
			// case is surfaced by the separate paused-campaign notification instead.
			foreach ( $campaigns as $campaign ) {
				if ( CampaignType::PERFORMANCE_MAX === ( $campaign['type'] ?? null )
					&& CampaignStatus::ENABLED === ( $campaign['status'] ?? null )
				) {
					return false;
				}
			}
		} catch ( ExceptionWithResponseData $e ) {
			// If the campaigns can't be retrieved, don't nag the merchant.
			return false;
		}

		return true;
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::SKIPPED_CAMPAIGN_CREATION;
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return null;
	}

	/**
	 * The "skipped campaign" state turns on when onboarding finishes without an enabled
	 * campaign, and off when a campaign is created — so every event that flips it must bust
	 * the cache, or a merchant who just skipped campaign creation would keep seeing the stale
	 * (pre-onboarding) result until the one-hour cache expires.
	 *
	 * Onboarding completes through different actions depending on the merchant type:
	 * - Service-based (ads-only) merchants fire `woocommerce_gla_onboarding_completed`.
	 * - Retail (shopping) merchants — who reach this notification by skipping campaign
	 *   creation — complete onboarding by syncing their Merchant Center settings, which fires
	 *   `woocommerce_gla_mc_settings_sync` (that action is what marks MC setup, and therefore
	 *   onboarding, complete for them). They never fire `woocommerce_gla_onboarding_completed`.
	 *
	 * The state turns off when Ads setup completes (`woocommerce_gla_ads_setup_completed`) or a
	 * campaign is created/updated (`woocommerce_gla_updated_campaign`).
	 *
	 * @return string[]
	 */
	public function get_invalidation_hooks(): array {
		return [
			'woocommerce_gla_onboarding_completed',
			'woocommerce_gla_mc_settings_sync',
			'woocommerce_gla_ads_setup_completed',
			'woocommerce_gla_updated_campaign',
		];
	}
}
