/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import {
	GOOGLE_MERCHANT_CENTER_URL,
	PRIMARY_MARKET_ID,
} from '~/pages/markets/constants';
import TrackableLink from '~/components/trackable-link';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import './index.scss';

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
		<Notice
			className="gla-shipping-notice"
			isDismissible={ false }
			status="info"
		>
			{ createInterpolateElement(
				__(
					'Shipping is managed in Google Merchant Center. Configure shipping rates and times for each currency in your <link>Merchant Center account</link>.',
					'google-listings-and-ads'
				),
				{
					link: (
						<TrackableLink
							target="_blank"
							type="external"
							href={ GOOGLE_MERCHANT_CENTER_URL }
							eventName="gla_shipping_notice_merchant_center_link_click"
							eventProps={ {
								url: GOOGLE_MERCHANT_CENTER_URL,
							} }
						/>
					),
				}
			) }
		</Notice>
	);
};

export default ShippingNotice;
