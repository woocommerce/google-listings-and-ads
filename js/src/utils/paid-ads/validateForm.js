/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Validate paid ads form. Accepts the form values object and returns errors object.
 *
 * @param {Object} values Form values.
 * @param {Array<string>} values.country Selected country for the paid ads campaign.
 * @param {number} values.amount The daily average cost amount.
 * @return {Object} errors.
 */
const validateForm = ( values ) => {
	const errors = {};

	if ( values.country.length === 0 ) {
		errors.country = __(
			'Please select a country for your ads campaign.',
			'google-listings-and-ads'
		);
	}

	if ( values.amount <= 0 ) {
		errors.amount = __(
			'Please make sure daily average cost is greater than 0.',
			'google-listings-and-ads'
		);
	}

	return errors;
};

export default validateForm;
