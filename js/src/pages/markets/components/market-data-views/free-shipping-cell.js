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
 * @typedef {Object} FreeShippingOptions
 * @property {number} [free_shipping_threshold] Order amount above which shipping is free.
 * @property {string} [currency] ISO 4217 currency code for the threshold.
 */

/**
 * @typedef {Object} FreeShippingRateConfig
 * @property {number} rate Flat shipping rate. 0 means unconditionally free.
 * @property {FreeShippingOptions} options Optional free-shipping threshold settings.
 */

/**
 * @typedef {Object} FreeShippingCellRow
 * @property {FreeShippingRateConfig} [shipping_rate_config] Shipping rate configuration.
 */

/**
 * Renders the free-shipping status for a market row.
 *
 * - No config → "-"
 * - `rate === 0` → "Free"
 * - `free_shipping_threshold` set → "Over <amount>" (currency-formatted)
 * - Otherwise → "-"
 *
 * @param {Object} props
 * @param {FreeShippingCellRow} props.market Market data row.
 * @return {JSX.Element|string} Free-shipping label, or "-".
 */
const FreeShippingCell = ( { market } ) => {
	const { shipping_rate_config } = market;

	if ( shipping_rate_config === null || shipping_rate_config === undefined ) {
		return '-';
	}

	const { rate, options } = shipping_rate_config;

	if ( rate === 0 ) {
		return __( 'Free', 'google-listings-and-ads' );
	}

	const threshold = options?.free_shipping_threshold;
	if ( threshold !== null && threshold !== undefined ) {
		return createInterpolateElement(
			// translators: <amount> is a currency-formatted free shipping threshold, e.g. "$50.00".
			__( 'Over <amount/>', 'google-listings-and-ads' ),
			{
				amount: (
					<FormattedAmount
						amount={ threshold }
						currencyCode={ options?.currency }
					/>
				),
			}
		);
	}

	return '-';
};

export default FreeShippingCell;
