/**
 * Internal dependencies
 */
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import AppSpinner from '.~/components/app-spinner';
import AccountCard from '.~/components/account-card';
import RequestFullAccessGoogleAccountCard from '../google-account-card/request-full-access-google-account-card';
import ConnectGoogleComboAccountCard from './connect-google-combo-account-card';
import ConnectedGoogleComboAccountCard from './connected-google-combo-account-card';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';

export default function GoogleComboAccountCard( { disabled = false } ) {
	const { google, scope, hasFinishedResolution } = useGoogleAccount();
	/* const { data: existingMCAccounts, isResolving: MCAccountsResolving } =
		useExistingGoogleMCAccounts();
	const {
		existingAccounts: existingAdsAccount,
		isResolving: AdsAccountsResolving,
	} = useExistingGoogleAdsAccounts(); */

	if (
		! hasFinishedResolution
	) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	const isConnected = google?.active === 'yes';

	if ( isConnected && scope.glaRequired ) {
		return (
			<ConnectedGoogleComboAccountCard
				googleAccount={ google }
				MCAccount={ [] }
				AdsAccounts={ [] }
			/>
		);
	}

	if ( isConnected && ! scope.glaRequired ) {
		return (
			<RequestFullAccessGoogleAccountCard
				additionalScopeEmail={ google.email }
			/>
		);
	}

	return <ConnectGoogleComboAccountCard disabled={ disabled } />;
}
