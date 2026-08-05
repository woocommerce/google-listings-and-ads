/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

const validlocationSet = new Set( [ 'all', 'selected' ] );
const validShippingRateSet = new Set( [ 'automatic', 'flat', 'manual' ] );
const validShippingTimeSet = new Set( [ 'flat', 'manual' ] );

const checkErrors = ( values ) => {
	const errors = {};

	// Check audience.
	if ( ! validlocationSet.has( values.location ) ) {
		errors.location = __(
			'Please select a location option.',
			'google-listings-and-ads'
		);
	}

	if ( values.location === 'selected' && values.countries.length === 0 ) {
		errors.countries = __(
			'Please select at least one country.',
			'google-listings-and-ads'
		);
	}

	/**
	 * Check shipping rates.
	 */
	if ( ! validShippingRateSet.has( values.shipping_rate ) ) {
		errors.shipping_rate = __(
			'Please select a shipping rate option.',
			'google-listings-and-ads'
		);
	}

	if (
		values.shipping_rate === 'flat' &&
		( values.flat_shipping_rate === undefined ||
			values.flat_shipping_rate < 0 )
	) {
		errors.flat_shipping_rate = __(
			'Please specify an estimated shipping rate.',
			'google-listings-and-ads'
		);
	}

	/**
	 * Check offer free shipping, only when shipping_rate is 'flat'.
	 */
	if ( values.shipping_rate === 'flat' ) {
		if (
			values.offer_free_shipping === true &&
			( values.shipping_country_rates ?? [] ).every(
				( shippingRate ) =>
					shippingRate.options.free_shipping_threshold === undefined
			)
		) {
			errors.free_shipping_threshold = __(
				'Please enter minimum order for free shipping.',
				'google-listings-and-ads'
			);
		}
	}

	/**
	 * Check shipping times.
	 */
	if ( ! validShippingTimeSet.has( values.shipping_time ) ) {
		errors.shipping_time = __(
			'Please select a shipping time option.',
			'google-listings-and-ads'
		);
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
