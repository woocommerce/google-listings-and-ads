/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MainTabNav from '../main-tab-nav';
import IssuesTableCard from './issues-table-card';
import ProductFeedTableCard from './product-feed-table-card';
import SubmissionSuccessGuide from './submission-success-guide';
import ProductStatistics from './product-statistics';
import ComingSoonNotice from './coming-soon-notice';
import './index.scss';
import useProductFeedPrefetch from './useProductFeedPrefetch';
import AppSpinner from '.~/components/app-spinner';
import isWCNavigationEnabled from '.~/utils/isWCNavigationEnabled';

const ProductFeed = () => {
	const { hasFinishedResolution, data } = useProductFeedPrefetch();
	const navigationEnabled = isWCNavigationEnabled();

	return (
		<>
			{ ! navigationEnabled && <MainTabNav /> }
			<SubmissionSuccessGuide />
			<ComingSoonNotice />
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
