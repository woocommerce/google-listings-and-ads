/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import SyncIcon from 'gridicons/dist/sync';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ErrorIcon from '.~/components/error-icon';
import AppDocumentationLink from '.~/components/app-documentation-link';

const DISAPPROVED = {
	title: __(
		'We’ve found unresolved issues in your account.',
		'google-listing-and-ads'
	),
	body: __(
		'Fix all account suspension issues listed below to request a review of your account.',
		'google-listing-and-ads'
	),
	requestButton: {
		disabled: false,
	},

	icon: <ErrorIcon />,
};
const WARNING = DISAPPROVED;
const BLOCKED = {
	...DISAPPROVED,
	body: createInterpolateElement(
		__(
			'Fix all account suspension issues listed below. You can request a new review approximately 7 days after a disapproval. <Link>Learn more</Link>',
			'google-listings-and-ads'
		),
		{
			Link: (
				<AppDocumentationLink
					href="https://support.google.com/merchants/answer/2948694"
					context="request-review"
					linkId="request-review-learn-more"
				/>
			),
		}
	),
	requestButton: {
		disabled: true,
	},
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
