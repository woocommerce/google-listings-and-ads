/**
 * Internal dependencies
 */
import Programs from './programs';
import Products from './products';
import SearchConsole from './search-console';
import getSelectedReportKey from '~/utils/getSelectedReportKey';
import useIsOrganicSearchReportAvailable from './search-console/hooks/useIsOrganicSearchReportAvailable';
import ExperienceRatingBanner from '~/components/experience-rating-banner';

const REPORTS = {
	programs: Programs,
	products: Products,
	'search-console': SearchConsole,
};

const Reports = () => {
	const isOrganicSearchAvailable = useIsOrganicSearchReportAvailable();
	const Report = REPORTS[ getSelectedReportKey( isOrganicSearchAvailable ) ];

	return (
		<>
			<ExperienceRatingBanner />
			<Report />
		</>
	);
};

export default Reports;

/**
 * @typedef {Object} MetricSchema Basic metric item structure for disaplying label and its currency type.
 * @property {string} key Metric key.
 * @property {string} label Metric label to display.
 * @property {boolean} [isCurrency] Metric is a currency if true. Needed to adjust Chart's valueType => y-axis labels.
 * @property {boolean} [reverseTrend] Whether a decrease is an improvement, which reverses the trend colours.
 * @property {string} [labelTooltipText] Text of an info tooltip shown next to the metric's label.
 */

/**
 * @typedef {Object} MetricFormatter Metric formatter structure.
 * @property {(value: number) => string} formatFn Function to format given value to the displayed text.
 *
 * @typedef {MetricSchema & MetricFormatter} Metric
 */

/**
 * @typedef {import('~/data/selectors').ReportSchema<ProductsReportData>} ProductsReportSchema
 * @typedef {import('~/data/selectors').ReportSchema<ProgramsReportData>} ProgramsReportSchema
 */

/**
 * @typedef {Object} ProductsReportData
 * @property {Array<ProductsData>} products Products data.
 * @property {Array<IntervalsData> | null} intervals Intervals data.
 * @property {PerformanceData} totals Performance data.
 */

/**
 * @typedef {Object} ProductsData
 * @property {number} id Product ID.
 * @property {TotalsData} subtotals Performance data.
 */

/**
 * @typedef {Object} ProgramsReportData
 * @property {Array<ProgramsData>} freeListings Product feed data
 * @property {Array<ProgramsData>} campaigns Ad campaigns data.
 * @property {Array<IntervalsData> | null} intervals Intervals data.
 * @property {PerformanceData} totals Performance data.
 */

/**
 * @typedef {Object} ProgramsData
 * @property {number} id ProgramId
 * @property {string} name Program's name.
 * @property {TotalsData} subtotals Performance data.
 */

/**
 * @typedef {Object} FreeListingsData
 * @property {TotalsData} subtotals Performance data.
 */

/**
 * @typedef {Object} IntervalsData
 * @property {string} interval ID of this report segment.
 * @property {TotalsData} subtotals Performance data.
 */

/**
 * @typedef { import("~/data/utils").ReportFieldsSchema } TotalsData
 * @typedef { import("~/data/utils").PerformanceData } PerformanceData
 */
