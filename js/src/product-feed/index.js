/**
 * Internal dependencies
 */
import TabNav from '../tab-nav';
import IssuesTableCard from './issues-table-card';
import ProductFeedTableCard from './product-feed-table-card';
import SubmissionSuccessGuide from './submission-success-guide';
import ProductStatistics from './product-statistics';
import ComingSoonNotice from './coming-soon-notice';
import './index.scss';

const ProductFeed = () => {
	return (
		<>
			<TabNav initialName="product-feed" />
			<SubmissionSuccessGuide />
			<ComingSoonNotice />
			<div className="gla-product-feed">
				<ProductStatistics />
				<IssuesTableCard />
				<ProductFeedTableCard trackEventReportId="product-feed" />
			</div>
		</>
	);
};

export default ProductFeed;
