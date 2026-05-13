/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import TrackableLink from '~/components/trackable-link';
import './index.scss';

/**
 *
 * @fires gla_documentation_link_click with `{ link_id: "shipping-rate-notice-shipping-settings", href: "https://merchants.google.com/" }`
 *
 * Displays an info notice about shipping being managed in Google Merchant Center.
 */
const ShippingRateNotice = () => {
	return (
		<Notice
			className="gla-shipping-rate-notice"
			isDismissible={ false }
			status="info"
		>
			{ createInterpolateElement(
				__(
					'Shipping rates are synced automatically from your WooCommerce <link>shipping settings</link>.',
					'google-listings-and-ads'
				),
				{
					link: (
						<TrackableLink
							target="_blank"
							type="external"
							href="admin.php?page=wc-settings&tab=shipping"
							eventName="gla_shipping_rate_notice_shipping_settings_link_click"
						/>
					),
				}
			) }
		</Notice>
	);
};

export default ShippingRateNotice;
