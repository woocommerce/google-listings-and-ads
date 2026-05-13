/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData, SHIPPING_RATE_METHOD } from '~/constants';
import { GOOGLE_MERCHANT_CENTER_URL } from '~/pages/markets/constants';
import AppDocumentationLink from '~/components/app-documentation-link';
import useSettings from '~/hooks/useSettings';
import './index.scss';

/**
 * Displays an info notice about shipping being managed in Google Merchant Center.
 *
 * @fires gla_documentation_link_click with `{ context: props.context, link_id: "market-notice-merchant-center", href: "https://merchants.google.com/" }`
 *
 * @param {Object} props
 * @param {string} props.context Tracking context forwarded to the documentation link (e.g. "edit-market-modal").
 */
const MarketNotice = ( { context } ) => {
	const { settings } = useSettings();

	if (
		glaData.isMultiLingualStore ||
		settings?.shipping_rate !== SHIPPING_RATE_METHOD.MANUAL
	) {
		return null;
	}

	return (
		<Notice
			className="gla-market-notice"
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
							context={ context }
							href={ GOOGLE_MERCHANT_CENTER_URL }
							linkId="market-notice-merchant-center"
						/>
					),
				}
			) }
		</Notice>
	);
};

export default MarketNotice;
