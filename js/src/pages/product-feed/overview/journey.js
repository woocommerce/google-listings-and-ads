/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Short badge label for each journey segment returned by the API.
 */
export const SEGMENT_LABEL = {
	live: __( 'Approved', 'google-listings-and-ads' ),
	expiring: __( 'Expiring', 'google-listings-and-ads' ),
	in_review: __( 'Pending review', 'google-listings-and-ads' ),
	waiting: __( 'Waiting to send', 'google-listings-and-ads' ),
	retrying: __( 'Retrying', 'google-listings-and-ads' ),
	disapproved: __( 'Disapproved', 'google-listings-and-ads' ),
	rejected: __( 'Data error', 'google-listings-and-ads' ),
	hidden: __( 'Don’t sync', 'google-listings-and-ads' ),
	not_sent: __( 'Not synced', 'google-listings-and-ads' ),
};

/**
 * Badge intent per segment: errors need a fix, info is in progress, success is live.
 */
export const SEGMENT_INTENT = {
	live: 'success',
	expiring: 'warning',
	in_review: 'info',
	waiting: 'info',
	retrying: 'info',
	disapproved: 'error',
	rejected: 'error',
	hidden: 'default',
	not_sent: 'default',
};

/**
 * Explanation of each state, shown on hover and read by screen readers.
 */
export const SEGMENT_HELP = {
	live: __( 'Approved and showing on Google.', 'google-listings-and-ads' ),
	expiring: __(
		'Still live. Resubmitted automatically before Google drops them.',
		'google-listings-and-ads'
	),
	in_review: __(
		'Google is checking these against its policies.',
		'google-listings-and-ads'
	),
	waiting: __(
		'Changed in your store and queued to be sent.',
		'google-listings-and-ads'
	),
	retrying: __(
		'Google had a temporary error. Retried automatically, nothing to fix.',
		'google-listings-and-ads'
	),
	disapproved: __(
		'Google turned these down after review. See the issues below.',
		'google-listings-and-ads'
	),
	rejected: __(
		'Google refused the product data when it was sent. Fix the product, then save it.',
		'google-listings-and-ads'
	),
	hidden: __(
		'Channel visibility is set to “Don’t sync and show”.',
		'google-listings-and-ads'
	),
	not_sent: __(
		'Not sent, usually because required product data is missing.',
		'google-listings-and-ads'
	),
};

/**
 * Display order: approved first, then states that need action, then work in
 * progress, then products that aren't syncing.
 */
export const SEGMENT_ORDER = [
	'live',
	'rejected',
	'disapproved',
	'retrying',
	'expiring',
	'waiting',
	'in_review',
	'not_sent',
	'hidden',
];
