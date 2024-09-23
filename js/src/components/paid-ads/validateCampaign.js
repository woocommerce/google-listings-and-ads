/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * @typedef {import('.~/components/types.js').CampaignFormValues} CampaignFormValues
 * @typedef {import('.~/components/types.js').ValidateCampaignOptions} ValidateCampaignOptions
 */

// Minimum percentage of the recommended daily budget.
const BUDGET_MIN_PERCENT = 0.3;

/**
 * Validate campaign form. Accepts the form values object and returns errors object.
 *
 * @param {CampaignFormValues} values Campaign form values.
 * @param {ValidateCampaignOptions} opts Extra form options.
 * @return {Object} errors.
 */
const validateCampaign = ( values, opts ) => {
	const errors = {};

	if ( ! Number.isFinite( values.amount ) || values.amount <= 0 ) {
		return {
			amount: __(
				'Please make sure daily average cost is greater than 0.',
				'google-listings-and-ads'
			),
		};
	}

	if (
		Number.isFinite( values?.amount ) &&
		Number.isFinite( opts?.dailyBudget )
	) {
		const { amount } = values;
		const { dailyBudget, formatAmount } = opts;
		const minAmount = Math.round( dailyBudget * BUDGET_MIN_PERCENT, 2 );

		if ( amount < minAmount ) {
			return {
				amount: sprintf(
					/* translators: %1$s: minimum daily budget */
					__(
						'Please make sure daily average cost is greater than %s.',
						'google-listings-and-ads'
					),
					formatAmount( minAmount )
				),
			};
		}
	}

	return errors;
};

export default validateCampaign;
