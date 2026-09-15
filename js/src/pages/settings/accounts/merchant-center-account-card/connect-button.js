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
 * The Merchant Center "Connect" button is clicked from Settings > Accounts.
 *
 * @event gla_set_up_merchant_center_click
 * @property {string} context The page context. Possible value: 'settings-linked-accounts'.
 */

/**
 * Renders the "Connect" button for the Merchant Center account card when the
 * account is not connected and the store is eligible for Merchant Center.
 *
 * @fires gla_set_up_merchant_center_click with `{ context: 'settings-linked-accounts' }`
 *
 * @return {JSX.Element} The connect button.
 */
const ConnectButton = () => {
	return (
		<AppButton
			href={ getOnboardingUrl() }
			eventName="gla_set_up_merchant_center_click"
			eventProps={ { context: 'settings-linked-accounts' } }
			isSecondary
		>
			{ __( 'Connect', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default ConnectButton;
