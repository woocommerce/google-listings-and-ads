/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import TrackableLink from '~/components/trackable-link';
import ShippingInfoNotice from '../../shipping-info-notice';

/**
 * @event gla_shipping_rate_notice_shipping_settings_link_click
 */

/**
 * Displays an info notice about shipping rates being synced from WooCommerce shipping settings.
 *
 * @fires gla_shipping_rate_notice_shipping_settings_link_click When the shipping settings link in the notice is clicked.
 */
const ShippingRateNotice = () => {
	return (
		<ShippingInfoNotice>
			{ createInterpolateElement(
				__(
					'Shipping rates are synced automatically from your WooCommerce <link>shipping settings</link>.',
					'google-listings-and-ads'
				),
				{
					link: (
						<TrackableLink
							eventName="gla_shipping_rate_notice_shipping_settings_link_click"
							href="admin.php?page=wc-settings&tab=shipping"
							target="_blank"
							type="external"
						/>
					),
				}
			) }
		</ShippingInfoNotice>
	);
};

export default ShippingRateNotice;
