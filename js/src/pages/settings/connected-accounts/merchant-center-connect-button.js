/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { getOnboardingUrl } from '~/utils/urls';

/**
 * The "Set up Merchant Center" button is clicked from Settings > Accounts.
 *
 * @event gla_set_up_merchant_center_click
 * @property {string} context The page context. Possible value: 'settings-linked-accounts'.
 */

/**
 * Renders the "Set up Merchant Center" button for the Merchant Center account
 * row, shown when the account is not connected but the store now has physical
 * products. Routes to the onboarding flow.
 *
 * @fires gla_set_up_merchant_center_click with `{ context: 'settings-linked-accounts' }`
 *
 * @return {JSX.Element} The connect button.
 */
export default function MerchantCenterConnectButton() {
	return (
		<AppButton
			href={ getOnboardingUrl() }
			eventName="gla_set_up_merchant_center_click"
			eventProps={ { context: 'settings-linked-accounts' } }
			isSecondary
		>
			{ __( 'Set up Merchant Center', 'google-listings-and-ads' ) }
		</AppButton>
	);
}
