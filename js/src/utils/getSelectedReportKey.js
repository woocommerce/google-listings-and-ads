/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Get the key of the Reports tab selected in the URL, falling back to the Programs tab.
 *
 * @param {boolean} [isOrganicSearchAvailable=false] Whether the Organic search tab is available.
 * @return {'programs'|'products'|'search-console'} The selected report key.
 */
const getSelectedReportKey = ( isOrganicSearchAvailable = false ) => {
	const reportKeys = [ 'programs', 'products' ];

	if ( isOrganicSearchAvailable ) {
		reportKeys.push( 'search-console' );
	}

	const query = getQuery();

	return reportKeys.includes( query?.reportKey )
		? query.reportKey
		: 'programs';
};

export default getSelectedReportKey;
