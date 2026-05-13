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
import { SHIPPING_RATE_METHOD } from '~/constants';
import { PRIMARY_MARKET_ID } from '../constants';

const checkErrors = ( values ) => {
	const errors = {};

	if ( values.id === PRIMARY_MARKET_ID ) {
		if ( ( values.countries ?? [] ).length === 0 ) {
			errors.countries = __(
				'Please select at least one country.',
				'google-listings-and-ads'
			);
		}

		return errors;
	}

	if ( ! values.country ) {
		errors.country = __(
			'Please select a market.',
			'google-listings-and-ads'
		);
	}

	if ( values.shipping_rate === SHIPPING_RATE_METHOD.FLAT ) {
		if (
			values.offer_free_shipping === true &&
			! values.free_shipping_threshold
		) {
			errors.free_shipping_threshold = __(
				'Please enter minimum order for free shipping.',
				'google-listings-and-ads'
			);
		}
	}

	if ( values.shipping_time === 'flat' ) {
		if (
			values.flat_shipping_min_time === null ||
			values.flat_shipping_min_time === undefined
		) {
			errors.flat_shipping_times = __(
				'Please specify an estimated minimum shipping time.',
				'google-listings-and-ads'
			);
		} else if (
			values.flat_shipping_max_time === null ||
			values.flat_shipping_max_time === undefined
		) {
			errors.flat_shipping_times = __(
				'Please specify an estimated maximum shipping time.',
				'google-listings-and-ads'
			);
		} else if (
			values.flat_shipping_min_time < 0 ||
			values.flat_shipping_max_time < 0
		) {
			errors.flat_shipping_times = __(
				'The shipping time cannot be less than 0.',
				'google-listings-and-ads'
			);
		} else if (
			values.flat_shipping_min_time > values.flat_shipping_max_time
		) {
			errors.flat_shipping_times = __(
				'The minimum shipping time must not be more than the maximum shipping time.',
				'google-listings-and-ads'
			);
		}
	}

	return errors;
};

export default checkErrors;
