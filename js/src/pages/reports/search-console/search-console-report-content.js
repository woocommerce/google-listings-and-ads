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
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS, glaData } from '~/constants';
import SearchConsoleReportFilters from './search-console-report-filters';
import DataFreshnessNotice from './data-freshness-notice';
import ConnectPrompt from './connect-prompt';
import OrganicOverview from './organic-overview';
import TopSearchQueries from './top-search-queries';
import TopProductPages from './top-product-pages';
import TopStorePages from './top-store-pages';

const TRACK_EVENT_ID = 'reports-search-console';

const { DISCONNECTED, TRANSIENT_ERROR } = GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS;

/**
 * The content of the Organic search report: Search Console data once connected, or a prompt to connect it.
 */
const SearchConsoleReportContent = () => {
	const { account, isConnected, hasFinishedResolution } =
		useGoogleSearchConsoleAccount();
	const { hasGoogleMCConnection } = useGoogleMCAccount();

	if ( ! hasFinishedResolution ) {
		return <AppSpinner />;
	}

	const status = account?.status;

	// A transient failure checking the status says nothing about the connection itself, so
	// rely on the stored connection data sent with the page instead.
	const showReport =
		status === TRANSIENT_ERROR
			? Boolean( glaData.searchConsoleConnected )
			: isConnected;

	if ( ! showReport ) {
		const needsAttention =
			Boolean( status ) &&
			status !== DISCONNECTED &&
			status !== TRANSIENT_ERROR;

		return <ConnectPrompt needsAttention={ needsAttention } />;
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
						lastUpdated={ account?.data_last_date ?? null }
					/>
				</FlexItem>
			</Flex>
			<OrganicOverview trackEventId={ TRACK_EVENT_ID } />
			<TopSearchQueries />
			{ hasGoogleMCConnection && <TopProductPages /> }
			<TopStorePages />
		</>
	);
};

export default SearchConsoleReportContent;
