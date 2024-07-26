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
import useProductsReport from './useProductsReport';
import useMetricsWithFormatter from '../useMetricsWithFormatter';
import useAdsCampaigns from '.~/hooks/useAdsCampaigns';
import AppSpinner from '.~/components/app-spinner';
import DifferentCurrencyNotice from '.~/components/different-currency-notice';
import NavigationClassic from '.~/components/navigation-classic';
import ProductsReportFilters from './products-report-filters';
import SummarySection from '../summary-section';
import ChartSection from '../chart-section';
import CompareProductsTableCard from './compare-products-table-card';
import ReportsNavigation from '../reports-navigation';
import RebrandingTour from '.~/components/tours/rebranding-tour';

/**
 * Available metrics and their human-readable labels.
 *
 * @type {Array<import('../index.js').MetricSchema>}
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
	{
		key: 'spend',
		label: __( 'Spend', 'google-listings-and-ads' ),
		isCurrency: true,
	},
];

/**
 * Renders query filters and results of products report.
 *
 * @param {Object} props React props.
 * @param {boolean} props.hasPaidSource Indicate whether display paid data source and relevant UIs.
 */
const ProductsReport = ( { hasPaidSource } ) => {
	const trackEventId = 'reports-products';
	const query = getQuery();

	const type = hasPaidSource
		? query[ REPORT_SOURCE_PARAM ] || REPORT_SOURCE_DEFAULT
		: REPORT_SOURCE_FREE;

	// Show only available data.
	// Until ~Q4 2021, free listings, may not have all metrics.
	const metrics = useMetricsWithFormatter(
		type === REPORT_SOURCE_PAID ? paidMetrics : freeMetrics
	);

	const {
		loaded,
		data: { totals, intervals, products },
	} = useProductsReport( type );

	return (
		<>
			<ProductsReportFilters
				hasPaidSource={ hasPaidSource }
				query={ query }
				trackEventId={ trackEventId }
			/>
			<SummarySection
				metrics={ metrics }
				loaded={ loaded }
				totals={ totals }
				trackEventId={ trackEventId }
			/>
			<ChartSection
				metrics={ metrics }
				loaded={ loaded }
				intervals={ intervals }
			/>
			<CompareProductsTableCard
				trackEventReportId={ trackEventId }
				metrics={ metrics }
				isLoading={ ! loaded }
				products={ products }
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
			<DifferentCurrencyNotice context="reports-products" />
			<NavigationClassic />
			<RebrandingTour />
			<ReportsNavigation />
			{ loaded ? (
				<ProductsReport hasPaidSource={ hasPaidSource } />
			) : (
				<AppSpinner />
			) }
		</>
	);
};

export default ProductsReportPage;
