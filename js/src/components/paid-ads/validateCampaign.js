/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * @typedef {import('.~/components/types.js').CampaignFormValues} CampaignFormValues
 */

/**
 * @typedef {Object} ValidateCampaignOptions
 * @property {number | undefined} dailyBudget Daily budget for the campaign.
 * @property {Function} formatAmount A function to format the budget amount according to the currency settings.
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

	if (
		Number.isFinite( values.amount ) &&
		Number.isFinite( opts?.dailyBudget )
	) {
		const { amount } = values;
		const { dailyBudget, formatAmount } = opts;
		const minAmount = Math.round( dailyBudget * BUDGET_MIN_PERCENT );

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

	if ( ! Number.isFinite( values.amount ) || values.amount <= 0 ) {
		return {
			amount: __(
				'Please make sure daily average cost is greater than 0.',
				'google-listings-and-ads'
			),
		};
	}

	return errors;
};

export default validateCampaign;
