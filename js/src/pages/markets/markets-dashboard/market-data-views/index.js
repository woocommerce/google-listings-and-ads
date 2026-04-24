/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Placeholder for the Markets data table.
 *
 * The follow-up task will replace this with a real DataViews-powered table.
 * The `shippingRate` is accepted now so that the next task can vary the
 * column set (see designs: `automatic`, `flat`, and `manual` show different
 * columns) without changing the parent.
 *
 * @param {Object} props
 * @param {string} [props.shippingRate] One of the values defined in `SHIPPING_RATE_OPTION`.
 */
const MarketDataViews = ( { shippingRate } ) => {
	return (
		<div
			className="gla-market-data-views"
			data-shipping-rate={ shippingRate }
		>
			{ __( 'MarketDataViews placeholder', 'google-listings-and-ads' ) }
		</div>
	);
};

export default MarketDataViews;
