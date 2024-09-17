/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

const validateShippingTimeGroup = ( values ) => {
	const errors = {};

	if ( values.countries.length === 0 ) {
		errors.countries = __(
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

	if ( values.time < 0 || values.maxTime < 0 ) {
		errors.time = __(
			'The estimated shipping time cannot be less than 0.',
			'google-listings-and-ads'
		);
	}

	if ( values.time > values.maxTime ) {
		errors.time = __(
			'The minimum shipping time must not be more than the maximum shipping time.',
			'google-listings-and-ads'
		);
	}

	return errors;
};

export default validateShippingTimeGroup;
