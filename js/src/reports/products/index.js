/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import {
	REPORT_SOURCE_PARAM,
	REPORT_SOURCE_FREE,
	REPORT_SOURCE_PAID,
	REPORT_SOURCE_DEFAULT,
} from '.~/constants';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import AppSpinner from '.~/components/app-spinner';
import TabNav from '../../tab-nav';
import ProductsReportFilters from './products-report-filters';
import SummarySection from './summary-section';
import ChartSection from '../chart-section';
import CompareProductsTableCard from './compare-products-table-card';

import chartData from './mocked-chart-data';
import SubNav from '../sub-nav';

/**
 * Available metrics and their human-readable labels.
 *
 * @type {Array<Metric>}
 */
const freeMetrics = [
	{
		key: 'clicks',
		label: __( 'Clicks', 'google-listings-and-ads' ),
	},
	{
		key: 'impressions',
		label: __( 'Impressions', 'google-listings-and-ads' ),
	},
];
const paidMetrics = [
	{
		key: 'sales',
		label: __( 'Total Sales', 'google-listings-and-ads' ),
		isCurrency: true,
	},
	{
		key: 'conversions',
		label: __( 'Conversions', 'google-listings-and-ads' ),
	},
	...freeMetrics,
];

/**
 * Renders query filters and results of products report.
 *
 * @param {Object} props React props.
 * @param {boolean} props.hasPaidSource Indicate whether display paid data source and relevant UIs.
 */
const ProductsReport = ( { hasPaidSource } ) => {
	const reportId = 'reports-products';
	const query = getQuery();

	const type = hasPaidSource
		? query[ REPORT_SOURCE_PARAM ] || REPORT_SOURCE_DEFAULT
		: REPORT_SOURCE_FREE;

	// Show only available data.
	// Until ~Q4 2021, free listings, may not have all metrics.
	const metrics = type === REPORT_SOURCE_PAID ? paidMetrics : freeMetrics;

	// Mocked report data for the chart.
	const report = {
		loaded: true,
		data: { intervals: chartData },
	};

	return (
		<>
			<ProductsReportFilters
				hasPaidSource={ hasPaidSource }
				query={ query }
				report={ reportId }
			/>
			<SummarySection metrics={ metrics } />
			<ChartSection metrics={ metrics } report={ report } />
			<CompareProductsTableCard
				trackEventReportId={ reportId }
				metrics={ metrics }
			/>
		</>
	);
};

/**
 * Renders a report page about products sold with GLA.
 */
const ProductsReportPage = () => {
	const { loaded, data: campaigns } = useAdsCampaigns();
	const hasPaidSource =
		loaded && campaigns.some( ( { status } ) => status === 'enabled' );

	return (
		<>
			<TabNav initialName="reports" />
			<SubNav initialName="products" />
			{ loaded ? (
				<ProductsReport hasPaidSource={ hasPaidSource } />
			) : (
				<AppSpinner />
			) }
		</>
	);
};

export default ProductsReportPage;

/**
 * @typedef {import("../index.js").Metric} Metric
 */
