/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SearchConsoleTopTable from '../search-console-top-table';
import useTopStorePages from '../hooks/useTopStorePages';
import ViewAllInSearchConsoleLink from '../view-all-in-search-console-link';

const NAME_COLUMN = {
	key: 'page',
	label: __( 'Page', 'google-listings-and-ads' ),
};

const getName = ( row ) => {
	return row.name;
};

const getSummaryLabel = ( { count, limit } ) => {
	if ( count === 0 ) {
		return null;
	}

	if ( count < limit ) {
		return sprintf(
			// translators: %d: number of pages listed.
			_n(
				'Showing %d page by clicks',
				'Showing %d pages by clicks',
				count,
				'google-listings-and-ads'
			),
			count
		);
	}

	return sprintf(
		// translators: %d: maximum number of pages listed.
		_n(
			'Showing top %d page by clicks',
			'Showing top %d pages by clicks',
			limit,
			'google-listings-and-ads'
		),
		limit
	);
};

/**
 * Table of the top pages that aren't products bringing people to the store, ranked by clicks.
 *
 * Unlike the top product pages, it is meant for every merchant, including those with no products.
 */
const TopStorePages = () => {
	const { hasFinishedResolution, property, pages, limit, days } =
		useTopStorePages();

	return (
		<SearchConsoleTopTable
			className="gla-search-console-table--stacked"
			trackEventReportId="reports-store-pages"
			title={ __( 'Top store pages', 'google-listings-and-ads' ) }
			actions={
				<ViewAllInSearchConsoleLink
					property={ property }
					days={ days }
					breakdown="page"
					eventName="gla_google_search_console_view_all_store_pages_link_click"
				/>
			}
			nameColumn={ NAME_COLUMN }
			items={ pages }
			getName={ getName }
			pagedParam="store_pages_paged"
			limit={ limit }
			getSummaryLabel={ getSummaryLabel }
			hasFinishedResolution={ hasFinishedResolution }
		/>
	);
};

export default TopStorePages;
