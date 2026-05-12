/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Checks for validation errors in the form values.
 *
 * @param {Object} values The form values to check for errors.
 * @param {Array} values.countries The list of selected countries.
 * @param {boolean} [values.offer_free_shipping] Whether free shipping over a threshold is enabled.
 * @param {number|null} [values.free_shipping] The free shipping threshold amount.
 * @return {Object} An object containing error messages for each invalid field.
 */
export const checkErrors = ( values ) => {
	const errors = {};

	if ( values.countries.length === 0 ) {
		errors.countries = __(
			'Please select at least one country.',
			'google-listings-and-ads'
		);
	}

	if ( values.offer_free_shipping && ! values.free_shipping ) {
		errors.free_shipping = __(
			'Please enter a minimum order value for free shipping.',
			'google-listings-and-ads'
		);
	}

	return errors;
};
