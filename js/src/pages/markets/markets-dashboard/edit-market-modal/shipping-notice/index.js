/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '~/components/app-documentation-link';
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import useSettings from '~/hooks/useSettings';
import { GOOGLE_MERCHANT_CENTER_URL } from '~/pages/markets/constants';
import './index.scss';

/**
 * Displays an info notice about shipping being managed in Google Merchant Center.
 */
const ShippingNotice = () => {
	const { settings } = useSettings();

	if (
		glaData.isMultiLingualStore ||
		settings?.shipping_rate !== SHIPPING_RATE_METHOD.MANUAL
	) {
		return null;
	}

	return (
		<Notice
			className="gla-edit-market-modal__notice"
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
						<AppDocumentationLink
							context="edit-market-modal"
							href={ GOOGLE_MERCHANT_CENTER_URL }
							linkId="shipping-notice-merchant-center"
						/>
					),
				}
			) }
		</Notice>
	);
};

export default ShippingNotice;
