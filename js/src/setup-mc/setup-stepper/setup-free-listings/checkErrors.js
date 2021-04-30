/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

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
	if ( ! values.shipping_rate ) {
		errors.shipping_rate = __(
			'Please select a shipping rate option.',
			'google-listings-and-ads'
		);
	}

	if (
		values.shipping_rate === 'flat' &&
		shippingRates.length < finalCountryCodes.length
	) {
		errors.shipping_rate = __(
			'Please specify shipping rates for all the countries.',
			'google-listings-and-ads'
		);
	}

	if (
		values.shipping_rate === 'flat' &&
		values.offers_free_shipping &&
		! values.free_shipping_threshold
	) {
		errors.free_shipping_threshold = __(
			'Please specify minimum order price for free shipping',
			'google-listings-and-ads'
		);
	}

	/**
	 * Check shipping times.
	 */
	if ( ! values.shipping_time ) {
		errors.shipping_time = __(
			'Please select a shipping time option.',
			'google-listings-and-ads'
		);
	}

	if (
		values.shipping_time === 'flat' &&
		shippingTimes.length < finalCountryCodes.length
	) {
		errors.shipping_time = __(
			'Please specify shipping times for all the countries.',
			'google-listings-and-ads'
		);
	}

	/**
	 * Check tax rate (required for U.S. only).
	 */
	if ( finalCountryCodes.includes( 'US' ) && ! values.tax_rate ) {
		errors.tax_rate = __(
			'Please specify tax rate option.',
			'google-listings-and-ads'
		);
	}

	/**
	 * Pre-launch checklist.
	 */
	if ( ! values.website_live ) {
		errors.website_live = __(
			'Please check the requirement.',
			'google-listings-and-ads'
		);
	}

	if ( ! values.checkout_process_secure ) {
		errors.checkout_process_secure = __(
			'Please check the requirement.',
			'google-listings-and-ads'
		);
	}

	if ( ! values.payment_methods_visible ) {
		errors.payment_methods_visible = __(
			'Please check the requirement.',
			'google-listings-and-ads'
		);
	}

	if ( ! values.refund_tos_visible ) {
		errors.refund_tos_visible = __(
			'Please check the requirement.',
			'google-listings-and-ads'
		);
	}

	if ( ! values.contact_info_visible ) {
		errors.contact_info_visible = __(
			'Please check the requirement.',
			'google-listings-and-ads'
		);
	}

	return errors;
};

export default checkErrors;
