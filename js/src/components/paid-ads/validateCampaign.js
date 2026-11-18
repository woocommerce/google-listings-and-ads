/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import getCharacterCounter from '~/utils/getCharacterCounter';
import { CAMPAIGN_NAME_MAX_LENGTH } from '~/constants';

/**
 * @typedef {import('~/components/types.js').CampaignFormValues} CampaignFormValues
 */

/**
 * @typedef {Object} ValidateCampaignOptions
 * @property {number|undefined} dailyBudget Daily budget for the campaign.
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

	// Counted the same way as the field's character counter, so the error and the counter agree.
	const countCharacters = getCharacterCounter( 'google-ads' );
	const campaignNameLength = countCharacters(
		values.campaignName?.trim() || ''
	);

	if ( campaignNameLength > CAMPAIGN_NAME_MAX_LENGTH ) {
		errors.campaignName = sprintf(
			/* translators: %d: maximum number of characters */
			__(
				'Please make sure the campaign name is %d characters or fewer.',
				'google-listings-and-ads'
			),
			CAMPAIGN_NAME_MAX_LENGTH
		);
	}

	// Only the amount entered by the user needs to be verified.
	if ( values.level !== 'custom' ) {
		return errors;
	}

	if (
		Number.isFinite( values.amount ) &&
		Number.isFinite( opts.dailyBudget ) &&
		opts.dailyBudget > 0
	) {
		const { amount } = values;
		const { dailyBudget, formatAmount } = opts;

		const minAmount = Math.ceil( dailyBudget * BUDGET_MIN_PERCENT );

		if ( amount < minAmount ) {
			errors.amount = sprintf(
				/* translators: %s: minimum daily budget */
				__(
					'Please make sure daily average cost is at least %s',
					'google-listings-and-ads'
				),
				formatAmount( minAmount )
			);
			return errors;
		}
	}

	if ( ! Number.isFinite( values.amount ) || values.amount <= 0 ) {
		errors.amount = __(
			'Please make sure daily average cost is greater than 0.',
			'google-listings-and-ads'
		);
	}

	return errors;
};

export default validateCampaign;
