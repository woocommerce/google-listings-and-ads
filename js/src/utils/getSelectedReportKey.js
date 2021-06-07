/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

const getSelectedReportKey = () => {
	const query = getQuery();

	return query?.reportKey === 'products' ? 'products' : 'programs';
};

export default getSelectedReportKey;
