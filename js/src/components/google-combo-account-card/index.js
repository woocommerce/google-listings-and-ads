/**
 * Internal dependencies
 */
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import AppSpinner from '~/components/app-spinner';
import AccountCard from '~/components/account-card';
import {
	RequestFullAccessGoogleAccountCard,
	AuthorizeWPComAppCard,
} from '~/components/google-account-card';
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
	const {
		google,
		scope,
		hasFinishedResolution: hasResolvedGoogle,
	} = useGoogleAccount();
	const { isWPComAppGranted, hasFinishedResolution: hasResolvedGMC } =
		useGoogleMCAccount();

	if ( ! hasResolvedGoogle || ! hasResolvedGMC ) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	const isConnected = google?.active === 'yes';

	if ( isConnected ) {
		if ( ! scope.onboardingRequired ) {
			return (
				<RequestFullAccessGoogleAccountCard
					additionalScopeEmail={ google.email }
				/>
			);
		}

		if ( ! isWPComAppGranted ) {
			return (
				<AuthorizeWPComAppCard
					eventPropsOfEnableButton={ { page: 'setup-mc' } }
				/>
			);
		}

		return <ConnectedGoogleComboAccountCard />;
	}

	return <ConnectGoogleComboAccountCard disabled={ disabled } />;
}
