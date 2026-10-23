/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import SearchConsoleTopTable from '../search-console-top-table';
import useTopProductPages from '../hooks/useTopProductPages';
import ViewAllInSearchConsoleLink from '../view-all-in-search-console-link';

const NAME_COLUMN = {
	key: 'product',
	label: __( 'Product', 'google-listings-and-ads' ),
};

const getName = ( row ) => row.name;

const getSummaryLabel = ( { count, limit } ) => {
	if ( count === 0 ) {
		return null;
	}

	if ( count < limit ) {
		return sprintf(
			// translators: %d: number of product pages listed.
			_n(
				'Showing %d product page by clicks',
				'Showing %d product pages by clicks',
				count,
				'google-listings-and-ads'
			),
			count
		);
	}

	return sprintf(
		// translators: %d: maximum number of product pages listed.
		_n(
			'Showing top %d product page by clicks',
			'Showing top %d product pages by clicks',
			limit,
			'google-listings-and-ads'
		),
		limit
	);
};

/**
 * Table of the top product pages bringing people to the store, ranked by clicks.
 *
 * Callers must not render it for service-based merchants, who have no products.
 */
const TopProductPages = () => {
	const { hasFinishedResolution, property, products, limit, days } =
		useTopProductPages();

	return (
		<SearchConsoleTopTable
			className="gla-search-console-table--stacked"
			trackEventReportId="reports-product-pages"
			title={ __( 'Top product pages', 'google-listings-and-ads' ) }
			actions={
				<ViewAllInSearchConsoleLink
					property={ property }
					days={ days }
					breakdown="page"
					eventName="gla_google_search_console_view_all_product_pages_link_click"
					pageContains={ glaData.productUrlBase }
				/>
			}
			nameColumn={ NAME_COLUMN }
			items={ products }
			getName={ getName }
			pagedParam="products_paged"
			limit={ limit }
			getSummaryLabel={ getSummaryLabel }
			hasFinishedResolution={ hasFinishedResolution }
		/>
	);
};

export default TopProductPages;
