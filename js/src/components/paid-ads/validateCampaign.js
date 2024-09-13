/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * @typedef {import('.~/components/types.js').CampaignFormValues} CampaignFormValues
 */

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
		Number.isFinite( opts?.budget?.daily_budget )
	) {
		const { amount } = values;
		const { budget, budgetMin } = opts;
		const { daily_budget: dailyBudget } = budget;
		const minAmount = Math.round( dailyBudget * budgetMin, 2 );

		if ( amount < minAmount ) {
			errors.amount = sprintf(
				/* translators: %s: minimum daily budget */
				__(
					'Please make sure daily average cost is at least %s.',
					'google-listings-and-ads'
				),
				minAmount
			);
		}
	}

	return errors;
};

export default validateCampaign;
