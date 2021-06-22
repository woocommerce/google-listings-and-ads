/**
 * Internal dependencies
 */
import { ProgramsReport, ProductsReport } from '.~/reports';
import getSelectedReportKey from '.~/utils/getSelectedReportKey';

const Reports = () => {
	const reportKey = getSelectedReportKey();

	if ( reportKey === 'products' ) {
		return <ProductsReport />;
	}

	return <ProgramsReport />;
};

export default Reports;
