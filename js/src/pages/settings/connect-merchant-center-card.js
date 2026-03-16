/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import useAdminUrl from '~/hooks/useAdminUrl';
import { getOnboardingUrl } from '~/utils/urls';

/**
 * Renders a card prompting the merchant to set up Google Merchant Center.
 *
 * Shown on the Settings page when the merchant was originally classified as
 * service-based (no physical products) but now has physical products, so
 * `serviceBasedMerchant` has flipped to `false` while MC remains unconnected.
 */
const ConnectMerchantCenterCard = () => {
	const serviceBasedMerchant = useServiceBasedMerchant();
	const { hasGoogleMCConnection } = useGoogleMCAccount();
	const adminUrl = useAdminUrl();

	if ( serviceBasedMerchant || hasGoogleMCConnection ) {
		return null;
	}

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			description={ __(
				'You now have physical products in your store. Connect a Google Merchant Center account to sync your products and list them on Google.',
				'google-listings-and-ads'
			) }
			indicator={
				<AppButton isPrimary href={ adminUrl + getOnboardingUrl() }>
					{ __(
						'Set up Merchant Center',
						'google-listings-and-ads'
					) }
				</AppButton>
			}
		/>
	);
};

export default ConnectMerchantCenterCard;
