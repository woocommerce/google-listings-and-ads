/**
 * Internal dependencies
 */
import useGoogleAccount from '~/hooks/useGoogleAccount';
import AppSpinner from '~/components/app-spinner';
import AccountCard from '~/components/account-card';
import { RequestFullAccessGoogleAccountCard } from '~/components/google-account-card';
import ConnectGoogleComboAccountCard from './connect-google-combo-account-card';
import ConnectedGoogleComboAccountCard from './connected-google-combo-account-card';
import './index.scss';

/**
 * Renders a card to connect, request full access, or display a connected Google account.
 *
 * Please note that this component is only used on the onboarding flow.
 *
 * @param {Object} props React props
 * @param {boolean} [props.disabled=false] Whether display the Card in disabled style.
 */
export default function GoogleComboAccountCard( { disabled = false } ) {
	const { google, scope, hasFinishedResolution } = useGoogleAccount();

	if ( ! hasFinishedResolution ) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	const isConnected = google?.active === 'yes';

	if ( isConnected && scope.onboardingRequired ) {
		return <ConnectedGoogleComboAccountCard />;
	}

	if ( isConnected && ! scope.onboardingRequired ) {
		return (
			<RequestFullAccessGoogleAccountCard
				additionalScopeEmail={ google.email }
			/>
		);
	}

	return <ConnectGoogleComboAccountCard disabled={ disabled } />;
}
