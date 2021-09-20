/**
 * Re-export only ther main report components.
 */
export { default as ProgramsReport } from './programs';
export { default as ProductsReport } from './products';

/**
 * @typedef {Object} Metric Metric item structure for disaplying label and its currency type.
 * @property {string} key Metric key.
 * @property {string} label Metric label to display.
 * @property {(currencyConfig: Object, value: number) => string} formatFn Function to format given number to the displayed text.
 * @property {boolean} [isCurrency] Metric is a currency if true. Needed to adjust Chart's valueType => y-axis labels.
 */

/**
 * @typedef {import('.~/data/selectors').ReportSchema<ProductsReportData>} ProductsReportSchema
 * @typedef {import('.~/data/selectors').ReportSchema<ProgramsReportData>} ProgramsReportSchema
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
 * @property {Array<ProgramsData>} campaigns Paid campaigns data.
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
 * @typedef { import(".~/data/utils").ReportFieldsSchema } TotalsData
 * @typedef { import(".~/data/utils").PerformanceData } PerformanceData
 */
