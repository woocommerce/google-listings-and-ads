/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export const GCR_ENROLLMENT_NOTICE_DISMISSED_KEY =
	'gla_reviews_gcr_enrollment_notice_dismissed';
export const GCR_ENROLLMENT_HELP_URL =
	'https://support.google.com/merchants/answer/14628991?hl=en';

export const DEFAULT_BADGE_WIDGET_POSITION = 'bottom-right';

export const BADGE_WIDGET_POSITION_OPTIONS = [
	{
		value: DEFAULT_BADGE_WIDGET_POSITION,
		label: __( 'Right bottom', 'google-listings-and-ads' ),
	},
	{
		value: 'bottom-left',
		label: __( 'Left bottom', 'google-listings-and-ads' ),
	},
];
