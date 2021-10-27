/**
 * Internal dependencies
 */
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import AppSpinner from '.~/components/app-spinner';
import AccountCard from '.~/components/account-card';
import AuthorizeGoogleAccountCard from './authorize-google-account-card';
import ConnectedGoogleAccountCard from './connected-google-account-card';

export default function GoogleAccountCard( { disabled = false } ) {
	const { google, scope, hasFinishedResolution } = useGoogleAccount();

	if ( ! hasFinishedResolution ) {
		return <AccountCard appearance={ {} } description={ <AppSpinner /> } />;
	}

	const isConnected = google?.active === 'yes';

	if ( isConnected && scope.gmcRequired ) {
		return <ConnectedGoogleAccountCard googleAccount={ google } />;
	}

	const additionalScopeEmail =
		isConnected && ! scope.gmcRequired ? google.email : undefined;

	return (
		<AuthorizeGoogleAccountCard
			additionalScopeEmail={ additionalScopeEmail }
			disabled={ disabled }
		/>
	);
}
