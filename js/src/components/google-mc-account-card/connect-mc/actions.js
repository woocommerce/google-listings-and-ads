/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CreateAccountButton from '../create-account-button';
import DisconnectAccountButton from '../disconnect-account-button';
import useExistingGoogleMCAccounts from '~/hooks/useExistingGoogleMCAccounts';

/**
 * Actions component.
 *
 * Conditionally renders either a button to disconnect the account if already
 * connected, or a button to create a new Merchant Center account.
 *
 * @param {Object}   props
 * @param {boolean}  props.isConnected Whether the Merchant Center account is connected.
 * @param {Object}   props.resultConnectMC The result of the connection request, used to handle loading state.
 * @param {Object}   props.resultCreateAccount The result of the create account request.
 * @param {Function} props.onCreateAccount Callback function for creating a new Merchant Center account.
 */
const Actions = ( {
	isConnected,
	resultConnectMC,
	resultCreateAccount,
	onCreateAccount,
} ) => {
	const { data: existingGoogleMCAccounts } = useExistingGoogleMCAccounts();

	if ( isConnected && existingGoogleMCAccounts.length > 0 ) {
		const handleOnDisconnected = () => {
			resultConnectMC.reset();
			resultCreateAccount.reset();
		};

		return (
			<DisconnectAccountButton
				onDisconnected={ handleOnDisconnected }
				isTertiary
			/>
		);
	}

	return (
		<CreateAccountButton
			disabled={ resultConnectMC.loading }
			onCreateAccount={ onCreateAccount }
			isTertiary
		>
			{ __(
				'Or, create a new Merchant Center account',
				'google-listings-and-ads'
			) }
		</CreateAccountButton>
	);
};

export default Actions;
