/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import SyncIcon from 'gridicons/dist/sync';

/**
 * Internal dependencies
 */
import ErrorIcon from '.~/components/error-icon';

const DISAPPROVED = {
	title: __(
		'We’ve found unresolved issues in your account.',
		'google-listing-and-ads'
	),
	body: __(
		'Fix all account suspension issues listed below to request a review of your account.',
		'google-listing-and-ads'
	),
	showRequestButton: true,
	icon: <ErrorIcon />,
};
const WARNING = DISAPPROVED;
const BLOCKED = {
	...DISAPPROVED,
	body: __(
		'Fix all account suspension issues listed below.',
		'google-listing-and-ads'
	),
};

const REVIEW_STATUSES = {
	UNDER_REVIEW: {
		title: __( 'Account review in progress.', 'google-listing-and-ads' ),
		body: __(
			'Review requests can take up to 7 days. You will receive an email notification once the review has been completed.',
			'google-listing-and-ads'
		),
		icon: <SyncIcon className="gla-sync-icon" size={ 18 } />,
	},
	DISAPPROVED,
	WARNING,
	BLOCKED,
};

export default REVIEW_STATUSES;
