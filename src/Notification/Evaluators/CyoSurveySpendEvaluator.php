<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

defined( 'ABSPATH' ) || exit;

/**
 * Class CyoSurveySpendEvaluator
 *
 * Fires for an eligible store with Google Ads spend since onboarding (the long CYO survey).
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class CyoSurveySpendEvaluator extends AbstractCyoSurveyEvaluator {

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return self::SPEND_SURVEY_ID;
	}

	/**
	 * The spend status this survey is shown for.
	 *
	 * @return string
	 */
	protected function get_spend_status_to_show(): string {
		return self::SPEND_STATUS_SPENT;
	}
}
