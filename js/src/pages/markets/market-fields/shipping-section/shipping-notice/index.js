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
import AppDocumentationLink from '~/components/app-documentation-link';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import './index.scss';

/**
 *
 * @fires gla_documentation_link_click with `{ link_id: "shipping-notice-merchant-center", href: "https://merchants.google.com/" }`
 *
 * Displays an info notice about shipping being managed in Google Merchant Center.
 */
const ShippingNotice = () => {
	const { values } = useAdaptiveFormContext();
	const isPrimaryMarket = values.id === PRIMARY_MARKET_ID;

	if (
		! (
			( ! glaData.isMultiLingualStore && isPrimaryMarket ) ||
			glaData.isMultiLingualStore
		)
	) {
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
						<AppDocumentationLink
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
