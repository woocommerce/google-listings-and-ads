/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import FormattedAmount from './formatted-amount';

/**
 * @typedef {import('~/data/actions').MarketShipping} MarketShipping
 */

/**
 * @typedef {Object} FreeShippingCellRow
 * @property {MarketShipping} [shipping] Market's shipping configuration.
 * @property {string[]} [currency] ISO 4217 currency codes assigned to the market.
 */

/**
 * Renders the free-shipping status for a market row.
 *
 * - No shipping configured → "-"
 * - `flat_rate === 0` → "Free"
 * - `free_shipping_threshold` set → "Over <amount>" (currency-formatted)
 * - Otherwise → "-"
 *
 * @param {Object} props
 * @param {FreeShippingCellRow} props.market Market data row.
 * @return {JSX.Element|string} Free-shipping label, or "-".
 */
const FreeShippingCell = ( { market } ) => {
	const { flat_rate: rate, free_shipping_threshold: threshold } =
		market.shipping ?? {};

	if ( rate === null || rate === undefined ) {
		return '-';
	}

	if ( rate === 0 ) {
		return __( 'Free', 'google-listings-and-ads' );
	}

	if ( threshold !== null && threshold !== undefined ) {
		return createInterpolateElement(
			// translators: <amount> is a currency-formatted free shipping threshold, e.g. "$50.00".
			__( 'Over <amount/>', 'google-listings-and-ads' ),
			{
				amount: (
					<FormattedAmount
						amount={ threshold }
						currencyCode={ market?.currency?.[ 0 ] }
					/>
				),
			}
		);
	}

	return '-';
};

export default FreeShippingCell;
