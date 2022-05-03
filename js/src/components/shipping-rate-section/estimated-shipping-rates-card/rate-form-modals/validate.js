/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * @typedef { import("../typedefs.js").ShippingRateGroup } ShippingRateGroup
 */

/**
 * @param {ShippingRateGroup} values
 */
const validate = ( values ) => {
	const errors = {};

	if ( values.countries.length === 0 ) {
		errors.countries = __(
			'Please specify at least one country.',
			'google-listings-and-ads'
		);
	}

	if ( values.rate === undefined ) {
		errors.rate = __(
			'Please enter the estimated shipping rate.',
			'google-listings-and-ads'
		);
	}

	if ( values.rate < 0 ) {
		errors.rate = __(
			'The estimated shipping rate cannot be less than 0.',
			'google-listings-and-ads'
		);
	}

	return errors;
};

export default validate;
