/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import MainTabNav from '~/components/main-tab-nav';
import AppSpinner from '~/components/app-spinner';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import ReportsNavigation from '../reports-navigation';
import SearchConsoleReportFilters from './search-console-report-filters';
import DataFreshnessNotice from './data-freshness-notice';
import ConnectPrompt from './connect-prompt';
import TopSearchQueries from './top-search-queries';
import './index.scss';

const TRACK_EVENT_ID = 'reports-search-console';

/**
 * The Organic search report: Search Console data once connected, or a prompt to connect it.
 */
const SearchConsoleReport = () => {
	const { account, isConnected, hasFinishedResolution } =
		useGoogleSearchConsoleAccount();

	const renderContent = () => {
		if ( ! hasFinishedResolution ) {
			return <AppSpinner />;
		}

		if ( ! isConnected ) {
			const neverConnected =
				! account ||
				account.status ===
					GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.DISCONNECTED;

			return <ConnectPrompt needsAttention={ ! neverConnected } />;
		}

		return (
			<>
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
							lastUpdated={ account.data_last_updated }
						/>
					</FlexItem>
				</Flex>
				<TopSearchQueries />
			</>
		);
	};

	return (
		<>
			<MainTabNav />
			<ReportsNavigation />
			{ renderContent() }
		</>
	);
};

export default SearchConsoleReport;
