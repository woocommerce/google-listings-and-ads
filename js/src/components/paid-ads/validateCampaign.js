/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * @typedef {import('.~/components/types.js').CampaignFormValues} CampaignFormValues
 */

/**
 * Validate campaign form. Accepts the form values object and returns errors object.
 *
 * @param {CampaignFormValues} values Campaign form values.
 * @return {Object} errors.
 */
const validateCampaign = ( values ) => {
	const errors = {};

	if ( values.countryCodes.length === 0 ) {
		errors.countryCodes = __(
			'Please select at least one country for your ads campaign.',
			'google-listings-and-ads'
		);
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
