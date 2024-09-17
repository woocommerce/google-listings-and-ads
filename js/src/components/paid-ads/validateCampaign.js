/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * @typedef {import('.~/components/types.js').CampaignFormValues} CampaignFormValues
 */

// Minimum percentage of the recommended daily budget.
const BUDGET_MIN_PERCENT = 0.3;

/**
 * Validate campaign form. Accepts the form values object and returns errors object.
 *
 * @param {CampaignFormValues} values Campaign form values.
 * @param {Object} opts Extra form options.
 * @return {Object} errors.
 */
const validateCampaign = ( values, opts ) => {
	const errors = {};

	if ( ! Number.isFinite( values.amount ) || values.amount <= 0 ) {
		errors.amount = __(
			'Please make sure daily average cost is greater than 0.',
			'google-listings-and-ads'
		);
	}

	if (
		Number.isFinite( values?.amount ) &&
		Number.isFinite( opts?.dailyBudget ) &&
		typeof opts?.formatNumber === 'function'
	) {
		const { amount } = values;
		const { dailyBudget, currency, formatNumber } = opts;
		const minAmount = Math.round( dailyBudget * BUDGET_MIN_PERCENT, 2 );

		if ( amount < minAmount ) {
			errors.amount = sprintf(
				/* translators: %1$s: minimum daily budget, %2$s: currency */
				__(
					'Please make sure daily average cost is greater than %1$s %2$s.',
					'google-listings-and-ads'
				),
				formatNumber( minAmount ),
				currency
			);
		}
	}

	return errors;
};

export default validateCampaign;
