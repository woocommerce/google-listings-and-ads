/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { ExternalLink } from '@wordpress/components';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { SHIPPING_RATE_METHOD } from '~/constants';
import {
	GOOGLE_MERCHANT_CENTER_URL,
	WC_SHIPPING_SETTINGS_URL,
} from '../constants';

/**
 * Returns the description shown under the "Markets" heading
 * for the given shipping rate selection.
 *
 * @param {string|undefined} shippingRate One of the values defined in `SHIPPING_RATE_METHOD`.
 * @return {JSX.Element|string|null} A localized description, or `null` when the value
 *                                   is unknown (e.g. settings are still resolving or the
 *                                   merchant skipped onboarding) so the caller can render
 *                                   a loading skeleton in its place.
 */
export const getShippingRateLabel = ( shippingRate ) => {
	switch ( shippingRate ) {
		case SHIPPING_RATE_METHOD.AUTOMATIC:
			return createInterpolateElement(
				__(
					'Shipping rates are synced from your <link>WooCommerce settings</link>.',
					'google-listings-and-ads'
				),
				{
					link: (
						<Link
							type="wp-admin"
							href={ WC_SHIPPING_SETTINGS_URL }
						/>
					),
				}
			);

		case SHIPPING_RATE_METHOD.FLAT:
			return __(
				'Shipping rates are manually configured per market.',
				'google-listings-and-ads'
			);

		case SHIPPING_RATE_METHOD.MANUAL:
			return createInterpolateElement(
				__(
					'Shipping is managed in <link>Google Merchant Center</link>.',
					'google-listings-and-ads'
				),
				{
					link: <ExternalLink href={ GOOGLE_MERCHANT_CENTER_URL } />,
				}
			);

		default:
			return null;
	}
};
