/**
 * External dependencies
 */
import noop from 'lodash/noop';
import {
	onQueryChange as defaultOnQueryChange,
	updateQueryString,
} from '@woocommerce/navigation';

/**
 * Creates the `onQueryChange` handler of a table that keeps its page in its own URL param.
 *
 * The table is ranked by one column only, so sorting is ignored. Its page is kept in its own URL
 * param so that several tables on a report don't share one page number.
 *
 * @param {string} pagedParam URL param holding the table's page number, e.g. `products_paged`.
 * @return {(param: string) => Function} Handler for `TableCard`'s `onQueryChange`.
 */
function createPagedQueryChangeHandler( pagedParam ) {
	// Level 1: called once per table, with the name of its page param.

	return function onQueryChange( param ) {
		// Level 2: called by `TableCard` with the name of what changed: `paged`, `sort`, `per_page`…

		if ( param === 'sort' ) {
			return noop;
		}

		if ( param === 'paged' ) {
			return function ( value ) {
				// Level 3: called by `TableCard` with the new value, e.g. the page number.
				updateQueryString( { [ pagedParam ]: value } );
			};
		}

		// WooCommerce's default handler.
		return defaultOnQueryChange( param );
	};
}

export default createPagedQueryChangeHandler;
