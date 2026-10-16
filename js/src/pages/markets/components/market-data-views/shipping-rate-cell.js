/**
 * Internal dependencies
 */
import FormattedAmount from './formatted-amount';

/**
 * @typedef {import('~/data/actions').MarketShipping} MarketShipping
 */

/**
 * @typedef {Object} ShippingRateCellRow
 * @property {MarketShipping} [shipping] Market's shipping configuration.
 */

/**
 * Renders the flat shipping rate for a market row as a formatted currency amount.
 *
 * Returns "-" when no shipping rate is configured; otherwise delegates to
 * `FormattedAmount`, which uses the Ads account currency for single-language
 * stores and the market's own currency for multilingual stores.
 *
 * @param {Object} props
 * @param {ShippingRateCellRow} props.market Market data row.
 * @return {JSX.Element|string} Formatted amount element, or "-".
 */
const ShippingRateCell = ( { market } ) => {
	const rate = market?.shipping?.flat_rate;

	if ( rate === null || rate === undefined ) {
		return '-';
	}

	return (
		<FormattedAmount
			amount={ rate }
			currencyCode={ market?.shipping?.currency }
		/>
	);
};

export default ShippingRateCell;
