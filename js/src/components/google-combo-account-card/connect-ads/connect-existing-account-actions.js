/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { DisconnectAccountButton } from '~/components/google-ads-account-card';
import useExistingGoogleAdsAccounts from '~/hooks/useExistingGoogleAdsAccounts';
import useGoogleAdsAccountStatus from '~/hooks/useGoogleAdsAccountStatus';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';

/**
 * Footer component.
 *
 * @param {Object} props Props.
 * @param {boolean} props.isConnected Whether the account is connected.
 * @param {Function} props.onCreateNewClick Callback when clicking on the button to create a new account.
 * @param {Function} [props.onDisconnected] Callback after the account is disconnected.
 * @param {boolean} props.disabled Whether to disable the create account button.
 * @param {Object} props.restProps Rest props. Passed to AppButton.
 * @return {JSX.Element} Footer component.
 */
const ConnectExistingAccountActions = ( {
	isConnected,
	onCreateNewClick,
	onDisconnected,
	disabled,
	...restProps
} ) => {
	const { existingAccounts } = useExistingGoogleAdsAccounts();
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { hasAccess } = useGoogleAdsAccountStatus();
	const shouldClaimGoogleAdsAccount = Boolean(
		googleAdsAccount?.id && hasAccess === false
	);

	if ( isConnected && existingAccounts.length > 0 ) {
		return <DisconnectAccountButton onDisconnected={ onDisconnected } />;
	}

	const disabledButton =
		disabled ||
		( shouldClaimGoogleAdsAccount && ! existingAccounts.length );
	return (
		<AppButton
			disabled={ disabledButton }
			onClick={ onCreateNewClick }
			isTertiary
			{ ...restProps }
		>
			{ __(
				'Or, create a new Google Ads account',
				'google-listings-and-ads'
			) }
		</AppButton>
	);
};

export default ConnectExistingAccountActions;
