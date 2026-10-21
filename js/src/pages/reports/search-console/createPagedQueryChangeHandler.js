/**
 * External dependencies
 */
import noop from 'lodash/noop';
import { onQueryChange, updateQueryString } from '@woocommerce/navigation';

/**
 * Creates the `onQueryChange` handler of a Search Console table.
 *
 * The table is ranked by clicks only, so sorting is ignored, and its page is kept in its own URL
 * param so that several tables on the report don't share one page number.
 *
 * @param {string} pagedParam URL param holding the table's page number.
 * @return {(param: string) => Function} Handler for `TableCard`'s `onQueryChange`.
 */
const createPagedQueryChangeHandler = ( pagedParam ) => ( param ) => {
	if ( param === 'sort' ) {
		return noop;
	}

	if ( param === 'paged' ) {
		return ( value ) => updateQueryString( { [ pagedParam ]: value } );
	}

	return onQueryChange( param );
};

export default createPagedQueryChangeHandler;
