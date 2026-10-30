<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection as TagManagerConnection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Settings as TagManagerSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\ResettableNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;

defined( 'ABSPATH' ) || exit;

/**
 * Class TagManagerAdsConversionConflictEvaluator
 *
 * Fires when the connected Google Tag Manager container holds a Google Ads conversion tag while its
 * snippet is still added to the storefront by default, so the same conversions may be counted twice.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class TagManagerAdsConversionConflictEvaluator implements NotificationEvaluatorInterface, ResettableNotificationEvaluatorInterface, Service {

	/** @var TagManagerConnection */
	private $tag_manager_connection;

	/** @var TagManagerSettings */
	private $tag_manager_settings;

	/** @var OnboardingCompleted */
	private $onboarding_completed;

	/**
	 * TagManagerAdsConversionConflictEvaluator constructor.
	 *
	 * @param TagManagerConnection $tag_manager_connection
	 * @param TagManagerSettings   $tag_manager_settings
	 * @param OnboardingCompleted  $onboarding_completed
	 */
	public function __construct( TagManagerConnection $tag_manager_connection, TagManagerSettings $tag_manager_settings, OnboardingCompleted $onboarding_completed ) {
		$this->tag_manager_connection = $tag_manager_connection;
		$this->tag_manager_settings   = $tag_manager_settings;
		$this->onboarding_completed   = $onboarding_completed;
	}

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'tag-manager-ads-conversion-conflict';
	}

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * Not shown when the conflict was found at connection, as the snippet is then off by default and
	 * the merchant was already told, nor once the merchant has set the snippet toggle themselves, as
	 * the settings already explain the conflict next to it.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		if ( ! $this->onboarding_completed->is_onboarding_complete() ) {
			return false;
		}

		return $this->tag_manager_connection->has_ads_conversion_conflict()
			&& $this->tag_manager_settings->is_snippet_active()
			&& ! $this->tag_manager_settings->has_snippet_injection_choice();
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::TAG_MANAGER_ADS_CONVERSION_CONFLICT;
	}

	/**
	 * Once the conflict clears, a dismissed notification shows again if the conflict comes back.
	 *
	 * @return string[]
	 */
	public function get_reset_hooks(): array {
		return [ 'woocommerce_gla_tag_manager_ads_conversion_conflict_cleared' ];
	}

	/**
	 * Get the snooze duration in seconds for temporary dismissals.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return null;
	}
}
