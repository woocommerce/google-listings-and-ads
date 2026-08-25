/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * @typedef {import('~/data/actions').MarketShipping} MarketShipping
 */

/**
 * @typedef {Object} ShippingTimesRow
 * @property {MarketShipping} [shipping] Market's shipping configuration.
 */

/**
 * Renders the shipping time range for a market row.
 *
 * Returns "Same day" when both min and max are 0, "1 day" or "N days" when
 * min equals max, a range like "3 - 5 days" otherwise, or "-" when no
 * shipping time is configured.
 *
 * @param {Object} props
 * @param {ShippingTimesRow} props.market Market data row.
 * @return {string} Formatted shipping time label.
 */
const ShippingTimesCell = ( { market } ) => {
	const { flat_time: time, flat_max_time: maxTime } = market.shipping ?? {};

	if (
		time === null ||
		time === undefined ||
		maxTime === null ||
		maxTime === undefined
	) {
		return '-';
	}

	if ( time === 0 && maxTime === 0 ) {
		return __( 'Same day', 'google-listings-and-ads' );
	}

	if ( time === maxTime ) {
		return sprintf(
			// translators: %d: number of shipping days.
			_n( '%d day', '%d days', time, 'google-listings-and-ads' ),
			time
		);
	}

	return sprintf(
		// translators: 1: minimum shipping days, 2: maximum shipping days, 3: shipping days unit (day or days).
		__( '%1$d - %2$d %3$s', 'google-listings-and-ads' ),
		time,
		maxTime,
		_n( 'day', 'days', maxTime, 'google-listings-and-ads' )
	);
};

export default ShippingTimesCell;
