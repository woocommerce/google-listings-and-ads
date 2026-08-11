/**
 * Internal dependencies
 */
import FormattedAmount from './formatted-amount';

/**
 * @typedef {Object} ShippingRateConfig
 * @property {number} rate     Flat shipping rate amount.
 * @property {string} currency ISO 4217 currency code for the rate.
 */

/**
 * @typedef {Object} ShippingRateCellRow
 * @property {ShippingRateConfig} [shipping_rate_config] Shipping rate configuration.
 */

/**
 * Renders the flat shipping rate for a market row as a formatted currency amount.
 *
 * Returns "-" when no shipping rate config is present; otherwise delegates to
 * `FormattedAmount`, which uses the Ads account currency for single-language
 * stores and the market's own currency for multilingual stores.
 *
 * @param {Object} props
 * @param {ShippingRateCellRow} props.market Market data row.
 * @return {JSX.Element|string} Formatted amount element, or "-".
 */
const ShippingRateCell = ( { market } ) => {
	if ( ! market?.shipping_rate_config ) {
		return '-';
	}

	const { rate, currency } = market.shipping_rate_config;

	return <FormattedAmount amount={ rate } currencyCode={ currency } />;
};

export default ShippingRateCell;
