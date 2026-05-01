<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionSchedulerInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsIncentives;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;

defined( 'ABSPATH' ) || exit;

/**
 * Class CheckUnclaimedIncentive
 *
 * Runs after a failed POST /wc/gla/ads/incentives attempt and decides whether the merchant
 * has an unclaimed incentive that can still be applied. Sets the
 * {@see OptionsInterface::ADS_HAS_UNCLAIMED_INCENTIVE} flag so the front end can offer
 * the merchant a chance to retry.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class CheckUnclaimedIncentive extends AbstractActionSchedulerJob implements StartOnHookInterface, OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * @var AdsIncentives
	 */
	protected $ads_incentives;

	/**
	 * @var Middleware
	 */
	protected $middleware;

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * @param ActionSchedulerInterface  $action_scheduler
	 * @param ActionSchedulerJobMonitor $monitor
	 * @param AdsIncentives             $ads_incentives
	 * @param Middleware                $middleware
	 * @param WC                        $wc
	 */
	public function __construct(
		ActionSchedulerInterface $action_scheduler,
		ActionSchedulerJobMonitor $monitor,
		AdsIncentives $ads_incentives,
		Middleware $middleware,
		WC $wc
	) {
		parent::__construct( $action_scheduler, $monitor );
		$this->ads_incentives = $ads_incentives;
		$this->middleware     = $middleware;
		$this->wc             = $wc;
	}

	/**
	 * Get the name of the job.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'check_unclaimed_incentive';
	}

	/**
	 * Get the start hook this job listens on.
	 *
	 * @return StartHook
	 */
	public function get_start_hook(): StartHook {
		return new StartHook( "{$this->get_hook_base_name()}start" );
	}

	/**
	 * Schedule the job.
	 *
	 * @param array $args
	 */
	public function schedule( array $args = [] ) {
		if ( $this->can_schedule() ) {
			$this->action_scheduler->schedule_immediate( $this->get_process_item_hook() );
		}
	}

	/**
	 * Process the job.
	 *
	 * @param array $items Unused.
	 */
	public function process_items( array $items ) {
		// Only proceed if a previous apply attempt failed.
		if ( 'error' !== $this->options->get( OptionsInterface::ADS_INCENTIVE_APPLY_ERROR ) ) {
			return;
		}

		$country_code  = $this->wc->get_base_country();
		$language_code = $this->get_language_code();

		// No incentives available
		$incentives = $this->ads_incentives->fetch_incentives( $country_code, $language_code );
		if ( empty( $incentives['incentives'] ) ) {
			$this->mark_no_unclaimed_incentive();
			return;
		}

		// Credits already applied
		$credits = $this->middleware->get_incentive_credits();
		if ( ! empty( $credits ) ) {
			$this->mark_no_unclaimed_incentive();
			return;
		}

		// Incentives available
		$this->options->update( OptionsInterface::ADS_HAS_UNCLAIMED_INCENTIVE, true );
	}

	/**
	 * Clear the unclaimed and the apply error flags.
	 */
	protected function mark_no_unclaimed_incentive(): void {
		$this->options->update( OptionsInterface::ADS_HAS_UNCLAIMED_INCENTIVE, false );
		$this->options->delete( OptionsInterface::ADS_INCENTIVE_APPLY_ERROR );
	}

	/**
	 * @return string
	 */
	protected function get_language_code(): string {
		$locale = get_locale();

		if ( empty( $locale ) ) {
			return 'en';
		}

		return strtolower( substr( $locale, 0, 2 ) );
	}
}
