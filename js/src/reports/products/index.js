/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import TabNav from '../../tab-nav';
import ProductsReportFilters from './products-report-filters';

const ProductsReport = () => {
	const reportId = 'reports-programs';

	return (
		<>
			<TabNav initialName="reports" />
			<ProductsReportFilters query={ getQuery() } report={ reportId } />
		</>
	);
};

export default ProductsReport;
