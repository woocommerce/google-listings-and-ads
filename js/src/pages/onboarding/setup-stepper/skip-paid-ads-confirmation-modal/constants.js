/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const OPTIONS = [
	{
		label: __( 'I already have ads on Google', 'google-listings-and-ads' ),
		value: 'i_already_have_ads_on_google',
		hasTextInput: false,
		optionType: 'checkbox',
	},
	{
		label: __(
			'I don’t have the budget to create ads now',
			'google-listings-and-ads'
		),
		value: 'i_dont_have_the_budget_to_create_ads_now',
		hasTextInput: false,
		optionType: 'checkbox',
	},
	{
		label: __(
			'I’ve tried Google ads before without success',
			'google-listings-and-ads'
		),
		value: 'ive_tried_google_ads_before_without_success',
		hasTextInput: false,
		optionType: 'checkbox',
	},
	{
		label: __( 'I don’t want ads on Google', 'google-listings-and-ads' ),
		value: 'i_dont_want_ads_on_google',
		hasTextInput: true,
		optionType: 'checkbox',
	},
	{
		label: __( 'I’ll create ads later', 'google-listings-and-ads' ),
		value: 'ill_create_ads_later',
		hasTextInput: true,
		optionType: 'checkbox',
	},
	{
		label: __( "I don't have time", 'google-listings-and-ads' ),
		value: 'i_dont_have_time',
		hasTextInput: false,
		optionType: 'checkbox',
		notice: __(
			'Are you sure? Setup takes less than 1 minute.',
			'google-listings-and-ads'
		),
	},
	{
		label: __( 'Other', 'google-listings-and-ads' ),
		value: 'other',
		hasTextInput: true,
		optionType: 'checkbox',
	},
	{
		label: __( 'Your role (optional)', 'google-listings-and-ads' ),
		value: 'your_role',
		hasTextInput: false,
		optionType: 'select',
		options: [
			{
				label: __( 'Select an option', 'google-listings-and-ads' ),
				value: '',
			},
			{
				label: __( 'Owner', 'google-listings-and-ads' ),
				value: 'owner',
			},
			{
				label: __( 'Developer', 'google-listings-and-ads' ),
				value: 'developer',
			},
			{
				label: __( 'Agency', 'google-listings-and-ads' ),
				value: 'agency',
			},
			{
				label: __( 'Marketing lead', 'google-listings-and-ads' ),
				value: 'marketing_lead',
			},
			{
				label: __( 'Other', 'google-listings-and-ads' ),
				value: 'other',
			},
		],
		otherInputTextPlaceholder: __(
			'Tell us your role',
			'google-listings-and-ads'
		),
	},
];
