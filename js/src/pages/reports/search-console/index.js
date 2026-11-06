/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import MainTabNav from '~/components/main-tab-nav';
import { recordGlaEvent } from '~/utils/tracks';
import ReportsNavigation from '../reports-navigation';
import SearchConsoleReportContent from './search-console-report-content';
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
	useEffect( () => {
		recordGlaEvent( 'gla_search_console_report_viewed' );
	}, [] );

	return (
		<>
			<MainTabNav />
			<ReportsNavigation />
			<SearchConsoleReportContent />
		</>
	);
};

export default SearchConsoleReport;
