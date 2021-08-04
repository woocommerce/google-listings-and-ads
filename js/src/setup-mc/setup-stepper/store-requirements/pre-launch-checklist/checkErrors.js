/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default function checkErrors( values ) {
	const errors = {};

	/**
	 * Pre-launch checklist.
	 */
	if ( values.website_live !== true ) {
		errors.website_live = __(
			'Please check the requirement.',
			'google-listings-and-ads'
		);
	}

	if ( values.checkout_process_secure !== true ) {
		errors.checkout_process_secure = __(
			'Please check the requirement.',
			'google-listings-and-ads'
		);
	}

	if ( values.payment_methods_visible !== true ) {
		errors.payment_methods_visible = __(
			'Please check the requirement.',
			'google-listings-and-ads'
		);
	}

	if ( values.refund_tos_visible !== true ) {
		errors.refund_tos_visible = __(
			'Please check the requirement.',
			'google-listings-and-ads'
		);
	}

	if ( values.contact_info_visible !== true ) {
		errors.contact_info_visible = __(
			'Please check the requirement.',
			'google-listings-and-ads'
		);
	}

	return errors;
}
