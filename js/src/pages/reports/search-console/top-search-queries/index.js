/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SearchConsoleTopTable from '../search-console-top-table';
import useTopSearchQueries from '../hooks/useTopSearchQueries';
import ViewAllInSearchConsoleLink from '../view-all-in-search-console-link';

const NAME_COLUMN = {
	key: 'query',
	label: __( 'Query', 'google-listings-and-ads' ),
};

const getName = ( row ) => row.query;

const getSummaryLabel = ( { count, limit } ) => {
	if ( count === 0 ) {
		return null;
	}

	if ( count < limit ) {
		return sprintf(
			// translators: %d: number of queries listed.
			_n(
				'Showing %d query by clicks',
				'Showing %d queries by clicks',
				count,
				'google-listings-and-ads'
			),
			count
		);
	}

	return sprintf(
		// translators: %d: maximum number of queries listed.
		_n(
			'Showing top %d query by clicks',
			'Showing top %d queries by clicks',
			limit,
			'google-listings-and-ads'
		),
		limit
	);
};

/**
 * Table of the top search queries bringing people to the store, ranked by clicks.
 */
const TopSearchQueries = () => {
	const { hasFinishedResolution, property, queries, limit, days } =
		useTopSearchQueries();

	return (
		<SearchConsoleTopTable
			trackEventReportId="reports-search-queries"
			title={ __( 'Top queries', 'google-listings-and-ads' ) }
			actions={
				<ViewAllInSearchConsoleLink
					property={ property }
					days={ days }
					breakdown="query"
					eventName="gla_google_search_console_view_all_queries_link_click"
				/>
			}
			nameColumn={ NAME_COLUMN }
			items={ queries }
			getName={ getName }
			pagedParam="queries_paged"
			limit={ limit }
			getSummaryLabel={ getSummaryLabel }
			hasFinishedResolution={ hasFinishedResolution }
		/>
	);
};

export default TopSearchQueries;
