/**
 * External dependencies
 */
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import MainTabNav from '~/components/main-tab-nav';
import AppSpinner from '~/components/app-spinner';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import { recordGlaEvent } from '~/utils/tracks';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS, glaData } from '~/constants';
import ReportsNavigation from '../reports-navigation';
import SearchConsoleReportFilters from './search-console-report-filters';
import DataFreshnessNotice from './data-freshness-notice';
import ConnectPrompt from './connect-prompt';
import OrganicOverview from './organic-overview';
import TopSearchQueries from './top-search-queries';
import TopProductPages from './top-product-pages';
import './index.scss';

const TRACK_EVENT_ID = 'reports-search-console';

const { DISCONNECTED, TRANSIENT_ERROR } = GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS;

/**
 * The Organic search report is viewed.
 *
 * @event gla_search_console_report_viewed
 */

/**
 * The Organic search report: Search Console data once connected, or a prompt to connect it.
 *
 * @fires gla_search_console_report_viewed once when the report is first shown.
 */
const SearchConsoleReport = () => {
	const { account, isConnected, hasFinishedResolution } =
		useGoogleSearchConsoleAccount();
	const serviceBasedMerchant = useServiceBasedMerchant();

	useEffect( () => {
		recordGlaEvent( 'gla_search_console_report_viewed' );
	}, [] );

	const renderContent = () => {
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
							lastUpdated={ account?.data_last_updated ?? null }
						/>
					</FlexItem>
				</Flex>
				<OrganicOverview trackEventId={ TRACK_EVENT_ID } />
				<TopSearchQueries />
				{ ! serviceBasedMerchant && <TopProductPages /> }
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
