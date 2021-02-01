/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getQuery } from '@woocommerce/navigation';
import { SummaryList } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import TabNav from '../../tab-nav';
import ProductsReportFilters from './products-report-filters';
import MetricNumber from '../metric-number';
import getMetricsData from './mocked-metrics-data'; // Mocked data

/**
 * Available metrics and their human-readable labels.
 *
 * TODO consider DRYing/unifying with ProgramsReport~performanceMetrics
 *
 * @type {Array<Array<string>>}
 */
const performanceMetrics = [
	[ 'totalSales', __( 'Total Sales', 'google-listings-and-ads' ) ],
	[ 'conversions', __( 'Conversions', 'google-listings-and-ads' ) ],
	[ 'clicks', __( 'Clicks', 'google-listings-and-ads' ) ],
	[ 'impressions', __( 'Impressions', 'google-listings-and-ads' ) ],
];

/**
 * Renders a report page about products sold with GLA.
 */
const ProductsReport = () => {
	const reportId = 'reports-programs';

	const metricsData = getMetricsData();

	// Show only available data.
	// Until ~Q4 2021, free listings, may not have all metrics.
	const availableMetrics = performanceMetrics.filter(
		( [ key ] ) => metricsData[ key ]
	);

	return (
		<>
			<TabNav initialName="reports" />
			<ProductsReportFilters query={ getQuery() } report={ reportId } />
			<SummaryList>
				{ () =>
					availableMetrics.map( ( [ key, label ] ) => (
						<MetricNumber
							key={ key }
							label={ label }
							data={ metricsData[ key ] }
						/>
					) )
				}
			</SummaryList>
		</>
	);
};

export default ProductsReport;
