/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import {
	GOOGLE_MERCHANT_CENTER_URL,
	PRIMARY_MARKET_ID,
} from '~/pages/markets/constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import ShippingInfoNotice from '../shipping-info-notice';

/**
 * @event gla_shipping_notice_merchant_center_link_click
 * @property {string} url The URL of the link that was clicked.
 */

/**
 * Displays an info notice about shipping being managed in Google Merchant Center.
 *
 * @fires gla_shipping_notice_merchant_center_link_click when the Merchant Center link in the notice is clicked.
 */
const ShippingNotice = () => {
	const { values } = useAdaptiveFormContext();
	const isPrimaryMarket = values.id === PRIMARY_MARKET_ID;

	if ( ! glaData.isMultiLingualStore && ! isPrimaryMarket ) {
		return null;
	}

	return (
		<ShippingInfoNotice
			className="gla-shipping-notice"
			message={ __(
				'Shipping is managed in Google Merchant Center. Configure shipping rates and times for each currency in your <link>Merchant Center account</link>.',
				'google-listings-and-ads'
			) }
			href={ GOOGLE_MERCHANT_CENTER_URL }
			eventName="gla_shipping_notice_merchant_center_link_click"
			eventProps={ { url: GOOGLE_MERCHANT_CENTER_URL } }
		/>
	);
};

export default ShippingNotice;
