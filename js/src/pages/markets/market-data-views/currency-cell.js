/**
 * @typedef {Object} CurrencyCellRow
 * @property {string[]} [currency] ISO 4217 currency codes assigned to the market.
 */

/**
 * Renders the currency list for a market row as a comma-separated string.
 *
 * Returns "-" when no currencies are present.
 *
 * @param {Object}          props
 * @param {CurrencyCellRow} props.market Market data row.
 * @return {string} Comma-separated currency codes, or "-".
 */
const CurrencyCell = ( { market } ) => {
	return market?.currency?.join( ', ' ) || '-';
};

export default CurrencyCell;
