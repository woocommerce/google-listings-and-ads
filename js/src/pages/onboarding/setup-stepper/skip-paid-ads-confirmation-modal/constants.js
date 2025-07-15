/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const OPTIONS = [
	{
		label: __( 'I already have ads on Google', 'google-listings-and-ads' ),
		value: 'i_already_have_ads_on_google',
		hasTextInput: false,
	},
	{
		label: __(
			'I don’t have the budget to create ads now',
			'google-listings-and-ads'
		),
		value: 'i_dont_have_the_budget_to_create_ads_now',
		hasTextInput: false,
	},
	{
		label: __(
			'I’ve tried Google ads before without success',
			'google-listings-and-ads'
		),
		value: 'ive_tried_google_ads_before_without_success',
		hasTextInput: false,
	},
	{
		label: __( 'I don’t want ads on Google', 'google-listings-and-ads' ),
		value: 'i_dont_want_ads_on_google',
		hasTextInput: true,
	},
	{
		label: __( 'I’ll create ads later', 'google-listings-and-ads' ),
		value: 'ill_create_ads_later',
		hasTextInput: true,
	},
	{
		label: __( 'Other', 'google-listings-and-ads' ),
		value: 'other',
		hasTextInput: true,
	},
];
