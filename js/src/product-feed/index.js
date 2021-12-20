/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import NavigationClassic from '.~/components/navigation-classic';
import IssuesTableCard from './issues-table-card';
import ProductFeedTableCard from './product-feed-table-card';
import SubmissionSuccessGuide from './submission-success-guide';
import CustomerEffortScorePrompt from '.~/components/customer-effort-score-prompt';
import ProductStatistics from './product-statistics';
import './index.scss';
import useProductFeedPrefetch from './useProductFeedPrefetch';
import AppSpinner from '.~/components/app-spinner';
import { GUIDE_NAMES, LOCAL_STORAGE_KEYS } from '.~/constants';
import localStorage from '.~/utils/localStorage';
import isWCTracksEnabled from '.~/utils/isWCTracksEnabled';

const ProductFeed = () => {
	const { hasFinishedResolution, data } = useProductFeedPrefetch();

	// Show submission success guide modal by visiting the path with a specific query `guide=submission-success`.
	// For example: `/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fproduct-feed&guide=submission-success`.
	const isSubmissionSuccessOpen =
		getQuery()?.guide === GUIDE_NAMES.SUBMISSION_SUCCESS;

	const canCESPromptOpen = localStorage.get(
		LOCAL_STORAGE_KEYS.CAN_ONBOARDING_SETUP_CES_PROMPT_OPEN
	);

	const wcTracksEnabled = isWCTracksEnabled();

	const shouldOpenCESPrompt =
		! isSubmissionSuccessOpen && canCESPromptOpen && wcTracksEnabled;

	return (
		<>
			<NavigationClassic />
			{ isSubmissionSuccessOpen && <SubmissionSuccessGuide /> }
			{ shouldOpenCESPrompt && (
				<CustomerEffortScorePrompt
					label={ __(
						'How easy was it to set up Google Listings & Ads?',
						'google-listings-and-ads'
					) }
					eventContext="gla-setup"
				/>
			) }
			<div className="gla-product-feed">
				{ ! hasFinishedResolution && <AppSpinner /> }
				{ hasFinishedResolution &&
					! data &&
					__(
						'An error occurred while retrieving your product feed. Please try again later.',
						'google-listings-and-ads'
					) }
				{ hasFinishedResolution && data && (
					<>
						<ProductStatistics />
						<IssuesTableCard />
						<ProductFeedTableCard trackEventReportId="product-feed" />
					</>
				) }
			</div>
		</>
	);
};

export default ProductFeed;
