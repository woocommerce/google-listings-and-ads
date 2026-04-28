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

	if ( serviceBasedMerchant || hasGoogleMCConnection ) {
		return null;
	}

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			detail={ __(
				'You now have physical products in your store. Connect a Google Merchant Center account to sync your products and list them on Google.',
				'google-listings-and-ads'
			) }
			indicator={
				<AppButton
					href={ getOnboardingUrl() }
					eventName="gla_set_up_merchant_center_click"
					eventProps={ { context: 'settings-linked-accounts' } }
					isSecondary
				>
					{ __(
						'Set up Merchant Center',
						'google-listings-and-ads'
					) }
				</AppButton>
			}
			expandedDetail
		/>
	);
};

export default ConnectMerchantCenterCard;
