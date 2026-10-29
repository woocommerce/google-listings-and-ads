/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppSpinner from '~/components/app-spinner';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import SearchConsoleReportFilters from './search-console-report-filters';
import DataFreshnessNotice from './data-freshness-notice';
import ConnectPrompt from './connect-prompt';
import OrganicOverview from './organic-overview';
import TopSearchQueries from './top-search-queries';
import TopProductPages from './top-product-pages';
import TopStorePages from './top-store-pages';

/**
 * The content of the Organic search report: Search Console data once connected, or a prompt to connect it.
 */
const SearchConsoleReportContent = () => {
	const { account, isReportAvailable, hasFinishedResolution } =
		useGoogleSearchConsoleAccount();
	const { hasGoogleMCConnection } = useGoogleMCAccount();

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
				align="flex-start"
			>
				<FlexBlock>
					<SearchConsoleReportFilters query={ getQuery() } />
				</FlexBlock>
				<FlexItem>
					<DataFreshnessNotice
						lastUpdated={ account?.data_last_date ?? null }
					/>
				</FlexItem>
			</Flex>
			<OrganicOverview />
			<TopSearchQueries />
			{ hasGoogleMCConnection && <TopProductPages /> }
			<TopStorePages />
		</>
	);
};

export default SearchConsoleReportContent;
