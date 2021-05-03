/**
 * Internal dependencies
 */
import TabNav from '../tab-nav';
import IssuesTableCard from './issues-table-card';
import ProductFeedTableCard from './product-feed-table-card';
import SubmissionSuccessGuide from './submission-success-guide';
import ProductStatistics from './product-statistics';
import ComingSoonNotice from './coming-soon-notice';

const ProductFeed = () => {
	return (
		<>
			<TabNav initialName="product-feed" />
			<SubmissionSuccessGuide />
			<ComingSoonNotice />
			<ProductStatistics />
			<IssuesTableCard />
			<ProductFeedTableCard trackEventReportId="product-feed" />
		</>
	);
};

export default ProductFeed;
