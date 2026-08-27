/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Status from './status';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import { REVIEW_STATUSES } from '../../constants';

/**
 * Whether the Merchant Center account has any products across the feed buckets.
 *
 * @param {Object} [statistics] Product statistics counts keyed by bucket.
 * @return {boolean} True when at least one product is present.
 */
function hasProducts( statistics ) {
	if ( ! statistics ) {
		return false;
	}
	return Object.values( statistics ).some( ( count ) => count > 0 );
}

/**
 * Shows the user's Google Merchant Center account status: Disapproved, Warning,
 * Under review, Onboarding or Approved.
 *
 * The review status is derived from the account issues reported by the Merchant API,
 * which can't tell an approved account from a brand-new one (both report no issues).
 * An issue-free account with no synced products is therefore shown as Onboarding
 * rather than a misleading Approved.
 *
 * @return {JSX.Element|null} The component with the status
 */
const AccountStatus = () => {
	const account = useAppSelectDispatch( 'getMCReviewRequest' );
	const { data: productData } = useAppSelectDispatch(
		'getMCProductStatistics'
	);

	if ( ! account.hasFinishedResolution || ! account.data?.status ) {
		return null;
	}

	let statusKey = account.data.status;

	if ( statusKey === 'APPROVED' ) {
		const statistics = productData?.statistics;

		// `statistics` is undefined until the product-statistics store resolves; render nothing
		// until then rather than briefly flashing Approved.
		if ( ! statistics ) {
			return null;
		}

		if ( ! hasProducts( statistics ) ) {
			// Also applies to an account that was approved but has since had all its products
			// removed; MAPI reports no issues either way, so it can't be told apart from a
			// brand-new account.
			statusKey = 'ONBOARDING';
		}
	}

	const accountStatus = REVIEW_STATUSES[ statusKey ];

	if ( ! accountStatus ) {
		return null;
	}

	return (
		<Status
			className="gla-account-status"
			description={ accountStatus.statusDescription }
			icon={ accountStatus.icon }
			label={ accountStatus.status }
			title={ __( 'Account status:', 'google-listings-and-ads' ) }
		/>
	);
};

export default AccountStatus;
