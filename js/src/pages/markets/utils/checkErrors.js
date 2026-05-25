/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SHIPPING_RATE_METHOD, glaData } from '~/constants';
import { PRIMARY_MARKET_ID } from '../constants';

const checkErrors = ( values ) => {
	const { isMultiLingualStore } = glaData;
	const isPrimary = values.id === PRIMARY_MARKET_ID;
	const { shipping_rate } = values;
	const errors = {};

	// Audience validation: skip for non-primary, non-multilingual, MANUAL markets
	// (the audience field is not shown in the form for that combination).
	const validateAudience =
		isPrimary ||
		shipping_rate !== SHIPPING_RATE_METHOD.MANUAL ||
		isMultiLingualStore;

	if ( validateAudience ) {
		if ( isPrimary ) {
			if ( ( values.countries ?? [] ).length === 0 ) {
				errors.countries = __(
					'Please select at least one country.',
					'google-listings-and-ads'
				);
			}
		} else if ( ! values.country ) {
			errors.country = __(
				'Please select a market.',
				'google-listings-and-ads'
			);
		}
	}

	// Locale validation: language + currency for multilingual stores using non-flat shipping.
	if ( isMultiLingualStore && shipping_rate !== SHIPPING_RATE_METHOD.FLAT ) {
		if ( ( values.language ?? [] ).length === 0 ) {
			errors.language = __(
				'Please select at least one language.',
				'google-listings-and-ads'
			);
		}
		if ( ( values.currency ?? [] ).length === 0 ) {
			errors.currency = __(
				'Please select at least one currency.',
				'google-listings-and-ads'
			);
		}
	}

	if ( shipping_rate === SHIPPING_RATE_METHOD.FLAT ) {
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

	if (
		shipping_rate === SHIPPING_RATE_METHOD.FLAT ||
		shipping_rate === SHIPPING_RATE_METHOD.AUTOMATIC
	) {
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
