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
import ProductStatistics from './product-statistics';
import './index.scss';
import useProductFeedPrefetch from './useProductFeedPrefetch';
import AppSpinner from '.~/components/app-spinner';
import { GUIDE_NAMES } from '.~/constants';

const ProductFeed = () => {
	const { hasFinishedResolution, data } = useProductFeedPrefetch();

	// Show submission success guide modal by visiting the path with a specific query `guide=submission-success`.
	// For example: `/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fproduct-feed&guide=submission-success`.
	const isSubmissionSuccessOpen =
		getQuery().guide === GUIDE_NAMES.SUBMISSION_SUCCESS;

	return (
		<>
			<NavigationClassic />
			{ isSubmissionSuccessOpen && <SubmissionSuccessGuide /> }
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
