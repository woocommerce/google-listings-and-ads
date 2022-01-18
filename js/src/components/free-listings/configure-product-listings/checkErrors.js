/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

const validShippingRateSet = new Set( [ 'automatic', 'flat', 'manual' ] );
const validShippingTimeSet = new Set( [ 'flat', 'manual' ] );
const validTaxRateSet = new Set( [ 'destination', 'manual' ] );

const checkErrors = (
	values,
	shippingRates,
	shippingTimes,
	finalCountryCodes
) => {
	const errors = {};

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
		( shippingRates.length < finalCountryCodes.length ||
			shippingRates.some( ( el ) => el.rate < 0 ) )
	) {
		errors.shipping_rate = __(
			'Please specify shipping rates for all the countries. And the estimated shipping rate cannot be less than 0.',
			'google-listings-and-ads'
		);
	}

	// When set up from scratch, the initial value of `free_shipping_threshold` is null.
	const threshold = values.free_shipping_threshold;
	if (
		values.shipping_rate === 'flat' &&
		values.offers_free_shipping &&
		( ! Number.isFinite( threshold ) || threshold < 0 )
	) {
		errors.free_shipping_threshold = __(
			'Please specify a valid minimum order price for free shipping',
			'google-listings-and-ads'
		);
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

	if (
		values.shipping_time === 'flat' &&
		( shippingTimes.length < finalCountryCodes.length ||
			shippingTimes.some( ( el ) => el.time < 0 ) )
	) {
		errors.shipping_time = __(
			'Please specify shipping times for all the countries. And the estimated shipping time cannot be less than 0.',
			'google-listings-and-ads'
		);
	}

	/**
	 * Check tax rate (required for U.S. only).
	 */
	if (
		finalCountryCodes.includes( 'US' ) &&
		! validTaxRateSet.has( values.tax_rate )
	) {
		errors.tax_rate = __(
			'Please specify tax rate option.',
			'google-listings-and-ads'
		);
	}

	return errors;
};

export default checkErrors;
