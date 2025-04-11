/**
 * Internal dependencies
 */
import useGoogleAccount from '~/hooks/useGoogleAccount';
import AppSpinner from '~/components/app-spinner';
import AccountCard from '~/components/account-card';
import RequestFullAccessGoogleAccountCard from './request-full-access-google-account-card';
import ConnectedGoogleAccountCard from './connected-google-account-card';
import ConnectGoogleAccountCard from './connect-google-account-card';

/**
 * Renders a card to connect, request full access, or display a connected Google account.
 *
 * Please note that this component is only used on the Reconnection page.
 * For the onboarding flow, the `GoogleComboAccountCard` component is used instead.
 * Therefore, the `scope` is checked for reconnection requirements.
 */
export default function GoogleAccountCard() {
	const { google, scope, hasFinishedResolution } = useGoogleAccount();

	if ( ! hasFinishedResolution ) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	const isConnected = google?.active === 'yes';

	if ( isConnected && scope.reconnectionRequired ) {
		return <ConnectedGoogleAccountCard googleAccount={ google } />;
	}

	if ( isConnected && ! scope.reconnectionRequired ) {
		return (
			<RequestFullAccessGoogleAccountCard
				additionalScopeEmail={ google.email }
			/>
		);
	}

	return <ConnectGoogleAccountCard />;
}
