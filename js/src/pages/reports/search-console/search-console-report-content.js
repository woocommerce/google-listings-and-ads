/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppSpinner from '~/components/app-spinner';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import SearchConsoleReportFilters from './search-console-report-filters';
import DataFreshnessNotice from './data-freshness-notice';
import ConnectPrompt from './connect-prompt';
import OrganicOverview from './organic-overview';
import TopSearchQueries from './top-search-queries';
import SearchConsoleTopPages from './search-console-top-pages';

/**
 * The content of the Organic search report: Search Console data once the report is available, or a prompt to connect it.
 */
const SearchConsoleReportContent = () => {
	const { account, isReportAvailable, hasFinishedResolution } =
		useGoogleSearchConsoleAccount();

	if ( ! hasFinishedResolution ) {
		return <AppSpinner />;
	}

	if ( ! isReportAvailable ) {
		return <ConnectPrompt />;
	}

	return (
		<>
			<Flex
				className="gla-search-console-report__header"
				align="flex-end"
			>
				<FlexBlock>
					<SearchConsoleReportFilters />
				</FlexBlock>
				<FlexItem>
					<DataFreshnessNotice
						lastUpdated={ account?.data_last_updated ?? null }
					/>
				</FlexItem>
			</Flex>
			<OrganicOverview />
			<TopSearchQueries />
			<SearchConsoleTopPages />
		</>
	);
};

export default SearchConsoleReportContent;
