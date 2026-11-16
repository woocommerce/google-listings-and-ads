<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators;

defined( 'ABSPATH' ) || exit;

/**
 * Class CyoSurveyNoSpendEvaluator
 *
 * Fires for an eligible store without Google Ads spend since onboarding (the short CYO survey).
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators
 */
class CyoSurveyNoSpendEvaluator extends AbstractCyoSurveyEvaluator {

	/**
	 * Get the notification's unique ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return self::NO_SPEND_SURVEY_ID;
	}

	/**
	 * The spend status this survey is shown for.
	 *
	 * @return string
	 */
	protected function get_spend_status_to_show(): string {
		return self::SPEND_STATUS_NONE;
	}
}
