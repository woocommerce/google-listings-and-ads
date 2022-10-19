/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * Validate paid ads form. Accepts the form values object and returns errors object.
 *
 * @param {Object} values Form values.
 * @param {Array<CountryCode>} values.countryCodes Selected country codes for the paid ads campaign.
 * @param {number} values.amount The daily average cost amount.
 * @return {Object} errors.
 */
const validateForm = ( values ) => {
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

export default validateForm;
