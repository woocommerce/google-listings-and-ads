/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

const REPORT_KEYS = [ 'programs', 'products', 'search-console' ];

/**
 * Get the key of the Reports tab selected in the URL, falling back to the Programs tab.
 *
 * @return {'programs'|'products'|'search-console'} The selected report key.
 */
const getSelectedReportKey = () => {
	const query = getQuery();

	return REPORT_KEYS.includes( query?.reportKey )
		? query.reportKey
		: 'programs';
};

export default getSelectedReportKey;
