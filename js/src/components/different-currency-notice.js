/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import AppDocumentationLink from '.~/components/app-documentation-link';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '.~/constants';

/**
 * Shows warning {@link Notice}
 * when the store's currency is different than the one set in Google Ads account.
 *
 * @param {Object} props React props.
 * @param {string} props.context Context or page on which the notice is shown, to be forwarded to the link's track event.
 * @return {JSX.Element} {@link Notice} element with the warning message and the link to the documentation.
 *
 * @fires gla_documentation_link_click with `{ context: "dashboard", link_id: "setting-up-currency", href: "https://support.google.com/google-ads/answer/9841530" }`
 * @fires gla_documentation_link_click with `{ context: "reports-products", link_id: "setting-up-currency", href: "https://support.google.com/google-ads/answer/9841530" }`
 * @fires gla_documentation_link_click with `{ context: "reports-programs", link_id: "setting-up-currency", href: "https://support.google.com/google-ads/answer/9841530" }`
 */
const DifferentCurrencyNotice = ( { context } ) => {
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { code: storeCurrency } = useStoreCurrency();

	// Do not render if data is not available, account not connected, or the same currencies are used.
	if (
		! googleAdsAccount ||
		googleAdsAccount.status !== GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED ||
		googleAdsAccount.currency === storeCurrency
	) {
		return null;
	}

	return (
		<Notice
			className="gla-different-currency-notice"
			status="warning"
			isDismissible={ false }
		>
			{ createInterpolateElement(
				__(
					'Note: The currency set in your Google Ads account is <adsCurrency />, which is different from your store currency, <storeCurrency />. <readMoreLink>Read more</readMoreLink>',
					'google-listings-and-ads'
				),
				{
					adsCurrency: <strong>{ googleAdsAccount.currency }</strong>,
					storeCurrency: <strong>{ storeCurrency }</strong>,
					// `ExternalIcon` is not addd here as that should be done uniformly across all `AppDocumentationLink`s:
					// https://github.com/woocommerce/google-listings-and-ads/issues/984
					readMoreLink: (
						<AppDocumentationLink
							className="gla-different-currency-notice__link"
							href="https://support.google.com/google-ads/answer/9841530"
							context={ context }
							linkId="setting-up-currency"
						/>
					),
				}
			) }
		</Notice>
	);
};

export default DifferentCurrencyNotice;
