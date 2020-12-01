/**
 * External dependencies
 */
import GridiconHelpOutline from 'gridicons/dist/help-outline';

/**
 * Internal dependencies
 */
import TabNav from '../tab-nav';
import Summaries from './summaries';
import IssuesTableCard from './issues-table-card';
import './index.scss';

/* TODO: The last updated date and time need to come from backend API. */
const lastUpdatedDateTime = new Date();

const ProductFeed = () => {
	return (
		<div className="gla-product-feed">
			<TabNav initialName="product-feed" />
			<div className="gla-product-feed__last-updated">
				{ /* TODO: Find the right WC way to format it. */ }
				Last updated: { lastUpdatedDateTime.toLocaleString() }
				<GridiconHelpOutline size={ 16 } />
			</div>
			<Summaries />
			<IssuesTableCard />
		</div>
	);
};

export default ProductFeed;
