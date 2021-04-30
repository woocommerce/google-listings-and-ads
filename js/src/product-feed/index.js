/**
 * External dependencies
 */
import { format as formatDate } from '@wordpress/date';

/**
 * Internal dependencies
 */
import TabNav from '../tab-nav';
import Summaries from './summaries';
import IssuesTableCard from './issues-table-card';
import ProductStatusHelpPopover from './product-status-help-popover';
import ProductFeedTableCard from './product-feed-table-card';
import SubmissionSuccessGuide from './submission-success-guide';
import ComingSoonNotice from './coming-soon-notice';
import './index.scss';

/* TODO: The last updated date and time need to come from backend API. */
const lastUpdatedDateTime = new Date();

const ProductFeed = () => {
	return (
		<div className="gla-product-feed">
			<TabNav initialName="product-feed" />
			<SubmissionSuccessGuide />
			<ComingSoonNotice />
			<div className="gla-product-feed__last-updated">
				Last updated:{ ' ' }
				{ formatDate( 'Y-m-d H:i:s', lastUpdatedDateTime ) }
				<ProductStatusHelpPopover />
			</div>
			<div className="gla-product-feed__summaries">
				<Summaries />
			</div>
			<IssuesTableCard />
			<ProductFeedTableCard trackEventReportId="product-feed" />
		</div>
	);
};

export default ProductFeed;
