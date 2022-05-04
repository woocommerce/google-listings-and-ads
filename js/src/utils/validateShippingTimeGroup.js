/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

const validateShippingTimeGroup = ( values ) => {
	const errors = {};

	if ( values.countryCodes.length === 0 ) {
		errors.countryCodes = __(
			'Please specify at least one country.',
			'google-listings-and-ads'
		);
	}

	if ( ! Number.isInteger( values.time ) ) {
		errors.time = __(
			'Please enter the estimated shipping time.',
			'google-listings-and-ads'
		);
	}

	if ( values.time < 0 ) {
		errors.time = __(
			'The estimated shipping time cannot be less than 0.',
			'google-listings-and-ads'
		);
	}

	return errors;
};

export default validateShippingTimeGroup;
