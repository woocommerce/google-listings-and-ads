/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ErrorIcon from '.~/components/error-icon';
import WarningIcon from '.~/components/warning-icon';
import SuccessIcon from '.~/components/success-icon';
import SyncIcon from '.~/components/sync-icon';

const DISAPPROVED = {
	status: (
		<span className="gla-error">
			{ __( 'Disapproved', 'google-listings-and-ads' ) }
		</span>
	),
	statusDescription: __(
		'To make products eligible to show on Google, fix all setup and policy issues that were found.',
		'google-listings-and-ads'
	),
	title: __(
		'We’ve found unresolved issues in your account.',
		'google-listings-and-ads'
	),
	body: __(
		'Fix all account suspension issues listed below to request a review of your account.',
		'google-listings-and-ads'
	),
	requestButton: true,
	icon: <ErrorIcon size={ 24 } />,
};

const WARNING = {
	...DISAPPROVED,
	status: __( 'Warning', 'google-listings-and-ads' ),
	statusDescription: __(
		'To keep showing your products on Google, fix your setup and policy issues.',
		'google-listings-and-ads'
	),
	icon: <WarningIcon size={ 24 } />,
};

const PENDING_REVIEW = {
	status: __( 'Pending review', 'google-listings-and-ads' ),
	statusDescription: __(
		'This may take up to 3 days. If approved, your products will show on Google once it’s completed.',
		'google-listings-and-ads'
	),
	icon: <SyncIcon size={ 24 } />,
};

const UNDER_REVIEW = {
	status: __( 'Under review', 'google-listings-and-ads' ),
	statusDescription: __(
		'Review requests take at least 7 days.',
		'google-listings-and-ads'
	),
	icon: <SyncIcon size={ 24 } />,
};

const APPROVED = {
	status: (
		<span className="gla-success">
			{ __( 'Approved', 'google-listings-and-ads' ) }
		</span>
	),
	statusDescription: __(
		'Your products listings are on Google.',
		'google-listings-and-ads'
	),
	icon: <SuccessIcon size={ 24 } />,
};

const ONBOARDING = {
	status: __( 'No products added', 'google-listings-and-ads' ),
	statusDescription: __(
		'Add and sync products to Google.',
		'google-listings-and-ads'
	),
	icon: <WarningIcon size={ 24 } />,
};

const REVIEW_STATUSES = {
	UNDER_REVIEW,
	PENDING_REVIEW,
	DISAPPROVED,
	WARNING,
	APPROVED,
	ONBOARDING,
};

export default REVIEW_STATUSES;
