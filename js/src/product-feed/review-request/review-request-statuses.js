/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ErrorIcon from '.~/components/error-icon';
import WarningIcon from '.~/components/warning-icon';
import SuccessIcon from '.~/components/success-icon';
import SyncIcon from '.~/components/sync-icon';
import AppDocumentationLink from '.~/components/app-documentation-link';

const DISAPPROVED = {
	status: (
		<span className="gla-status-error">
			{ __( 'Disapproved', 'google-listing-and-ads' ) }
		</span>
	),
	statusDescription: __(
		'To make products eligible to show on Google, fix all setup and policy issues that were found.',
		'google-listing-and-ads'
	),
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
	icon: <ErrorIcon size={ 24 } />,
};

const WARNING = {
	...DISAPPROVED,
	status: __( 'Warning', 'google-listing-and-ads' ),
	statusDescription: __(
		'To keep showing your products on Google, fix your setup and policy issues.',
		'google-listing-and-ads'
	),
	icon: <WarningIcon size={ 24 } />,
};

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

const PENDING_REVIEW = {
	status: __( 'Pending review', 'google-listing-and-ads' ),
	statusDescription: __(
		'This may take up to 3 days.If approved, your products will show on Google once it’s completed.',
		'google-listing-and-ads'
	),
	title: __( 'Account pending for review.', 'google-listing-and-ads' ),
	body: __(
		'This may take up to 3 days. You will receive an email notification once the review has been completed.',
		'google-listing-and-ads'
	),
	icon: <SyncIcon size={ 24 } />,
};

const UNDER_REVIEW = {
	status: __( 'Under review', 'google-listing-and-ads' ),
	statusDescription: __(
		'Review requests take at least 7 days. ',
		'google-listing-and-ads'
	),
	title: __( 'Account review in progress.', 'google-listing-and-ads' ),
	body: __(
		'Review requests can take up to 7 days. You will receive an email notification once the review has been completed.',
		'google-listing-and-ads'
	),
	icon: <SyncIcon size={ 24 } />,
};

const APPROVED = {
	status: (
		<span className="gla-status-success">
			{ __( 'Approved', 'google-listing-and-ads' ) }
		</span>
	),
	statusDescription: __(
		'Your products listings are on Google.',
		'google-listing-and-ads'
	),
	icon: <SuccessIcon size={ 24 } />,
};

const REVIEW_STATUSES = {
	PENDING_REVIEW,
	UNDER_REVIEW,
	DISAPPROVED,
	WARNING,
	BLOCKED,
	APPROVED,
};

export default REVIEW_STATUSES;
