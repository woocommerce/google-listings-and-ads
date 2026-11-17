<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Ads\AdsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsReport;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationCacheKeys;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\SiteScopedNotificationEvaluatorInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class AbstractCyoSurveyEvaluator
 *
 * Shared eligibility and spend check for the Choose Your Own (CYO) sign-up offer surveys.
 * A store is eligible when its onboarding completed on or after the CYO launch, at least
 * 60 days have passed since, and the campaign has not ended. Eligible stores are split by
 * whether they have Google Ads spend since onboarding; each subclass shows for one side.
 *
 * The spend status is cached once per store in a single transient that both subclasses read,
 * so they can never disagree and show both surveys at once. Dismissing either survey (including
 * through its CTA) ends the ask for the store, so the other one never shows afterwards.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
abstract class AbstractCyoSurveyEvaluator implements SiteScopedNotificationEvaluatorInterface, AdsAwareInterface, OptionsAwareInterface, Service {

	use AdsAwareTrait;
	use OptionsAwareTrait;

	protected const SPEND_SURVEY_ID    = 'cyo-survey-spend';
	protected const NO_SPEND_SURVEY_ID = 'cyo-survey-no-spend';

	protected const SPEND_STATUS_SPENT   = 'spent';
	protected const SPEND_STATUS_NONE    = 'none';
	protected const SPEND_STATUS_UNKNOWN = 'unknown';

	/** Onboarding must complete at or after this instant (2026-05-19T00:00:00Z, CYO launch). */
	private const ELIGIBLE_FROM = 1779148800;

	/** Surveys show only before this instant (2027-01-16T00:00:00Z). */
	private const VISIBLE_UNTIL = 1800057600;

	private const MINIMUM_DAYS_SINCE_ONBOARDING = 60;

	private const CACHE_ID = 'cyo-survey';

	/** @var OnboardingCompleted */
	protected $onboarding_completed;

	/** @var AdsReport */
	protected $ads_report;

	/**
	 * AbstractCyoSurveyEvaluator constructor.
	 *
	 * @param OnboardingCompleted $onboarding_completed
	 * @param AdsReport           $ads_report
	 */
	public function __construct( OnboardingCompleted $onboarding_completed, AdsReport $ads_report ) {
		$this->onboarding_completed = $onboarding_completed;
		$this->ads_report           = $ads_report;
	}

	/**
	 * The spend status (one of the SPEND_STATUS_* constants) this survey is shown for.
	 *
	 * @return string
	 */
	abstract protected function get_spend_status_to_show(): string;

	/**
	 * Whether the notification's condition is currently met.
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		$now = $this->get_now();

		if ( $now >= self::VISIBLE_UNTIL ) {
			return false;
		}

		$completed_at = $this->get_onboarding_completed_at();

		if ( $completed_at < self::ELIGIBLE_FROM ) {
			return false;
		}

		if ( ( $now - $completed_at ) < self::MINIMUM_DAYS_SINCE_ONBOARDING * DAY_IN_SECONDS ) {
			return false;
		}

		if ( $this->is_any_survey_dismissed() ) {
			return false;
		}

		return $this->get_spend_status( $completed_at, $now ) === $this->get_spend_status_to_show();
	}

	/**
	 * Get the notification's priority.
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return NotificationPriorities::CYO_SURVEY;
	}

	/**
	 * Dismissal is permanent.
	 *
	 * @return int|null
	 */
	public function get_snooze_duration(): ?int {
		return null;
	}

	/**
	 * Current Unix timestamp.
	 *
	 * @return int
	 */
	protected function get_now(): int {
		return time();
	}

	/**
	 * Onboarding completion timestamp, or 0 when onboarding is incomplete or the stored value
	 * is not a positive timestamp.
	 *
	 * @return int
	 */
	private function get_onboarding_completed_at(): int {
		if ( ! $this->onboarding_completed->is_onboarding_complete() ) {
			return 0;
		}

		$completed_at = $this->options->get( OptionsInterface::ONBOARDING_COMPLETED_AT );

		return is_numeric( $completed_at ) ? max( 0, (int) $completed_at ) : 0;
	}

	/**
	 * Whether the store dismissed either survey. Reads the site-scoped state that
	 * NotificationService::dismiss() writes, keyed by notification ID.
	 *
	 * @return bool
	 */
	private function is_any_survey_dismissed(): bool {
		$state = $this->options->get( OptionsInterface::NOTIFICATIONS_SITE_STATE, [] );

		if ( ! is_array( $state ) ) {
			return false;
		}

		foreach ( [ self::SPEND_SURVEY_ID, self::NO_SPEND_SURVEY_ID ] as $id ) {
			if ( is_array( $state[ $id ] ?? null ) && ! empty( $state[ $id ]['dismissed'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Spend status since onboarding, shared by both surveys through one transient.
	 *
	 * @param int $completed_at Onboarding completion timestamp.
	 * @param int $now          Current timestamp.
	 *
	 * @return string One of the SPEND_STATUS_* constants.
	 */
	private function get_spend_status( int $completed_at, int $now ): string {
		$cache_key = NotificationCacheKeys::for_site( self::CACHE_ID );
		$cached    = get_transient( $cache_key );

		if ( in_array( $cached, [ self::SPEND_STATUS_SPENT, self::SPEND_STATUS_NONE, self::SPEND_STATUS_UNKNOWN ], true ) ) {
			return $cached;
		}

		// Failures are cached for the same hour as results, matching the other notification
		// evaluators, so a failing Ads account is not queried on every admin page load.
		$status = $this->fetch_spend_status( $completed_at, $now );
		set_transient( $cache_key, $status, HOUR_IN_SECONDS );

		return $status;
	}

	/**
	 * Look up whether the store has Google Ads spend since onboarding.
	 *
	 * @param int $completed_at Onboarding completion timestamp.
	 * @param int $now          Current timestamp.
	 *
	 * @return string One of the SPEND_STATUS_* constants.
	 */
	private function fetch_spend_status( int $completed_at, int $now ): string {
		if ( ! $this->ads_service->is_setup_complete() ) {
			return self::SPEND_STATUS_NONE;
		}

		try {
			$report = $this->ads_report->get_report_data(
				'campaigns',
				[
					'fields' => [ 'spend' ],
					'after'  => gmdate( 'Y-m-d', $completed_at ),
					'before' => gmdate( 'Y-m-d', $now ),
				]
			);
		} catch ( Throwable $e ) {
			do_action( 'woocommerce_gla_exception', $e, __METHOD__ );

			return self::SPEND_STATUS_UNKNOWN;
		}

		return (float) ( $report['totals']['spend'] ?? 0 ) > 0 ? self::SPEND_STATUS_SPENT : self::SPEND_STATUS_NONE;
	}
}
