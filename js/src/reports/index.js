/**
 * Re-export only ther main report components.
 */
export { default as ProgramsReport } from './programs';
export { default as ProductsReport } from './products';

/**
 * @typedef {Object} Metric Metric item structure for disaplying label and its currency type.
 * @property {string} key Metric key.
 * @property {string} label Metric label to display.
 * @property {boolean} [isCurrency] Metric is a currency if true.
 */

/**
 * @typedef {Object} ProductsReportSchema
 * @property {boolean} loaded Whether the data have been loaded.
 * @property {ProductsReportData} data Fetched products report data.
 */

/**
 * @typedef {Object} ProductsReportData
 * @property {Array<ProductsData>} products Products data.
 * @property {Array<IntervalsData>} intervals Intervals data.
 * @property {PerformanceData} totals Performance data.
 */

/**
 * @typedef {Object} ProductsData
 * @property {number} id Product ID.
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
