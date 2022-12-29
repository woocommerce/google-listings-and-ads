/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import isNonFreeShippingRate from '.~/utils/isNonFreeShippingRate';

const validlocationSet = new Set( [ 'all', 'selected' ] );
const shippingRateConfigTypesSet = new Set( [ 'automatic', 'flat', 'manual' ] );
const shippingTimeConfigTypesSet = new Set( [ 'flat', 'automatic' ] );
const validTaxRateSet = new Set( [ 'destination', 'manual' ] );

const checkErrors = ( values, shippingTimes, finalCountryCodes ) => {
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
	 * Check shipping config type.
	 */
	if ( ! shippingRateConfigTypesSet.has( values.shippingConfigType ) ) {
		errors.shippingConfigType = __(
			'Please select a shipping option.',
			'google-listings-and-ads'
		);
	}

	/**
	 * Check shipping rates.
	 */
	if (
		values.shippingConfigType === 'flat' &&
		( values.shipping_country_rates.length < finalCountryCodes.length ||
			values.shipping_country_rates.some( ( el ) => el.rate < 0 ) )
	) {
		errors.shippingConfigType = __(
			'Please specify shipping rates for all the countries. And the estimated shipping rate cannot be less than 0.',
			'google-listings-and-ads'
		);
	}

	/**
	 * Check offer free shipping, only when shippingConfigType is 'flat'.
	 */
	if ( values.shippingConfigType === 'flat' ) {
		if (
			values.offer_free_shipping === undefined &&
			values.shipping_country_rates.some( isNonFreeShippingRate )
		) {
			errors.offer_free_shipping = __(
				'Please select an option for whether to offer free shipping.',
				'google-listings-and-ads'
			);
		}

		if (
			values.offer_free_shipping === true &&
			values.shipping_country_rates.every(
				( shippingRate ) =>
					shippingRate.options.free_shipping_threshold === undefined
			)
		) {
			errors.offer_free_shipping = __(
				'Please enter minimum order for free shipping.',
				'google-listings-and-ads'
			);
		}
	}

	/**
	 * Check shipping times.
	 */
	if (
		shippingTimeConfigTypesSet.has( values.shippingConfigType ) &&
		( shippingTimes.length < finalCountryCodes.length ||
			shippingTimes.some( ( el ) => el.time < 0 ) )
	) {
		errors.shippingConfigType = __(
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
