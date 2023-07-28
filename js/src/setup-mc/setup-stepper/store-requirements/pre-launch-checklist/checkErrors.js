/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export default function checkErrors( values ) {
	const errors = {};

	const preLaunchFields = [
		'website_live',
		'checkout_process_secure',
		'payment_methods_visible',
		'refund_tos_visible',
		'contact_info_visible',
	];

	if ( preLaunchFields.some( ( field ) => values[ field ] !== true ) ) {
		errors.preLaunchChecklist = __(
			'Please check all requirements.',
			'google-listings-and-ads'
		);
	}

	return errors;
}
