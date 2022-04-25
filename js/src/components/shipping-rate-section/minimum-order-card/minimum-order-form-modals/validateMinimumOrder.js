/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * @typedef { import("../typedefs.js").MinimumOrderGroup } MinimumOrderGroup
 */

/**
 * @param {MinimumOrderGroup} values
 */
const validateMinimumOrder = ( values ) => {
	const errors = {};

	if ( values.countries.length === 0 ) {
		errors.countries = __(
			'Please specify at least one country.',
			'google-listings-and-ads'
		);
	}

	if ( ! ( values.threshold > 0 ) ) {
		errors.threshold = __(
			'The minimum order amount must be greater than 0.',
			'google-listings-and-ads'
		);
	}

	return errors;
};

export default validateMinimumOrder;
