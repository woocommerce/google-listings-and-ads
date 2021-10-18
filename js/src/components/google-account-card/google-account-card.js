/**
 * Internal dependencies
 */
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import AppSpinner from '.~/components/app-spinner';
import AccountCard from '.~/components/account-card';
import AuthGoogleAccountCard from './auth-google-account-card';
import ConnectedGoogleAccountCard from './connected-google-account-card';

export default function GoogleAccountCard( { disabled = false } ) {
	const { google, hasFinishedResolution } = useGoogleAccount();

	if ( ! hasFinishedResolution ) {
		return <AccountCard appearance={ {} } description={ <AppSpinner /> } />;
	}

	if ( google?.active === 'yes' ) {
		return <ConnectedGoogleAccountCard googleAccount={ google } />;
	}

	return <AuthGoogleAccountCard disabled={ disabled } />;
}
