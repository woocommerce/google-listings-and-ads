/**
 * Internal dependencies
 */
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import AppSpinner from '.~/components/app-spinner';
import AccountCard from '.~/components/account-card';
import RequestFullAccessGoogleAccountCard from './request-full-access-google-account-card';
import ConnectedGoogleAccountCard from './connected-google-account-card';
import ConnectGoogleAccountCard from './connect-google-account-card';

export default function GoogleAccountCard( { disabled = false } ) {
	const { google, scope, hasFinishedResolution } = useGoogleAccount();

	if ( ! hasFinishedResolution ) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	const isConnected = google?.active === 'yes';

	if ( isConnected && scope.glaRequired ) {
		return <ConnectedGoogleAccountCard googleAccount={ google } />;
	}

	if ( isConnected && ! scope.glaRequired ) {
		return (
			<RequestFullAccessGoogleAccountCard
				additionalScopeEmail={ google.email }
			/>
		);
	}

	return <ConnectGoogleAccountCard disabled={ disabled } />;
}
