/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppSpinner from '~/components/app-spinner';
import MainTabNav from '~/components/main-tab-nav';
import { recordGlaEvent } from '~/utils/tracks';
import ReportsNavigation from '../reports-navigation';
import SearchConsoleReportContent from './search-console-report-content';
import useRefreshGoogleSearchConsoleAccount from './hooks/useRefreshGoogleSearchConsoleAccount';
import './index.scss';

/**
 * The Organic search report is viewed.
 *
 * @event gla_search_console_report_viewed
 */

/**
 * The Organic search report page: the navigation frame around the report content.
 *
 * @fires gla_search_console_report_viewed each time the Organic search tab is opened.
 */
const SearchConsoleReport = () => {
	const { isRefreshPending } = useRefreshGoogleSearchConsoleAccount();

	useEffect( () => {
		recordGlaEvent( 'gla_search_console_report_viewed' );
	}, [] );

	return (
		<>
			<MainTabNav />
			<ReportsNavigation />
			{ isRefreshPending ? (
				<AppSpinner />
			) : (
				<SearchConsoleReportContent />
			) }
		</>
	);
};

export default SearchConsoleReport;
