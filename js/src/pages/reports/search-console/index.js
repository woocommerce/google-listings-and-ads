/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import MainTabNav from '~/components/main-tab-nav';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import ReportsNavigation from '../reports-navigation';
import SearchConsoleReportFilters from './search-console-report-filters';
import DataFreshnessNotice from './data-freshness-notice';
import TopSearchQueries from './top-search-queries';
import './index.scss';

const TRACK_EVENT_ID = 'reports-search-console';

/**
 * The Organic search report, showing the connected Search Console property's data.
 */
const SearchConsoleReport = () => {
	const { account } = useGoogleSearchConsoleAccount();

	return (
		<>
			<MainTabNav />
			<ReportsNavigation />
			<Flex
				className="gla-search-console-report__header"
				align="flex-start"
			>
				<FlexBlock>
					<SearchConsoleReportFilters
						query={ getQuery() }
						trackEventId={ TRACK_EVENT_ID }
					/>
				</FlexBlock>
				<FlexItem>
					<DataFreshnessNotice
						lastUpdated={ account?.data_last_updated ?? null }
					/>
				</FlexItem>
			</Flex>
			<TopSearchQueries />
		</>
	);
};

export default SearchConsoleReport;
