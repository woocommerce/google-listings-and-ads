/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Checks for validation errors in the form values.
 *
 * @param {Object} values The form values to check for errors.
 * @param {Array} values.countries The list of selected countries.
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

	return errors;
};
