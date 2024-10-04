/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import CreateAccountButton from '.~/components/google-mc-account-card/create-account-button';
import DisconnectAccountButton from '.~/components/google-mc-account-card/disconnect-account-button';

/**
 * ConnectMCFooter component.
 *
 * This component renders the footer for the Merchant Center connection section.
 * It conditionally renders either a button to disconnect the account if already
 * connected, or a button to create a new Merchant Center account.
 *
 * @param {Object}   props
 * @param {boolean}  props.isConnected Whether the Merchant Center account is connected.
 * @param {Object}   props.resultConnectMC The result of the connection request, used to handle loading state.
 * @param {Function} props.handleCreateAccount Callback function for creating a new Merchant Center account.
 */
const ConnectMCFooter = ( {
	isConnected,
	resultConnectMC,
	handleCreateAccount,
} ) => {
	if ( isConnected ) {
		return <DisconnectAccountButton />;
	}

	return (
		<CreateAccountButton
			isLink
			disabled={ resultConnectMC.loading }
			onCreateAccount={ handleCreateAccount }
		>
			{ __(
				'Or, create a new Merchant Center account',
				'google-listings-and-ads'
			) }
		</CreateAccountButton>
	);
};

export default ConnectMCFooter;
