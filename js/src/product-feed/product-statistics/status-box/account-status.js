/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Status from '.~/product-feed/product-statistics/status-box/status';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import REVIEW_STATUSES from '.~/product-feed/review-request/review-request-statuses';

/**
 * Shows the user's Google Account status
 *
 * @return {JSX.Element|null} The component with the status
 */
const AccountStatus = () => {
	const account = useAppSelectDispatch( 'getMCReviewRequest' );

	if ( ! account.hasFinishedResolution || ! account.data ) {
		return null;
	}

	const accountStatus = REVIEW_STATUSES[ account.data.status ];

	return (
		<Status
			className="gla-account-status"
			title={ __( 'Account status:', 'google-listings-and-ads' ) }
			icon={ accountStatus.icon }
			label={ accountStatus.status }
			description={ accountStatus.statusDescription }
		/>
	);
};

export default AccountStatus;
