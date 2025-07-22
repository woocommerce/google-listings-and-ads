/**
 * Internal dependencies
 */
import Programs from './programs';
import Products from './products';
import getSelectedReportKey from '~/utils/getSelectedReportKey';
import ExperienceRatingBanner from '~/components/experience-rating-banner';

const Reports = () => {
	const reportKey = getSelectedReportKey();

	return (
		<>
			<ExperienceRatingBanner />
			{ reportKey === 'products' ? <Products /> : <Programs /> }
		</>
	);
};

export default Reports;

/**
 * @typedef {Object} MetricSchema Basic metric item structure for disaplying label and its currency type.
 * @property {string} key Metric key.
 * @property {string} label Metric label to display.
 * @property {boolean} [isCurrency] Metric is a currency if true. Needed to adjust Chart's valueType => y-axis labels.
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
 * @property {Array<ProgramsData>} freeListings Free listings data
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
