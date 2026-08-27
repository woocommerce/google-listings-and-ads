/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import { GOOGLE_MERCHANT_CENTER_URL } from '~/pages/markets/constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import TrackableLink from '~/components/trackable-link';
import ShippingInfoNotice from '../shipping-info-notice';
import isPrimaryMarket from '../../../../utils/isPrimaryMarket';

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

	if ( ! glaData.isMultiLingualStore && ! isPrimaryMarket( values ) ) {
		return null;
	}

	return (
		<ShippingInfoNotice>
			{ createInterpolateElement(
				__(
					'Shipping is managed in Google Merchant Center. Configure shipping rates and times for each currency in your <link>Merchant Center account</link>.',
					'google-listings-and-ads'
				),
				{
					link: (
						<TrackableLink
							eventName="gla_shipping_notice_merchant_center_link_click"
							eventProps={ { url: GOOGLE_MERCHANT_CENTER_URL } }
							href={ GOOGLE_MERCHANT_CENTER_URL }
							target="_blank"
							type="external"
						/>
					),
				}
			) }
		</ShippingInfoNotice>
	);
};

export default ShippingNotice;
