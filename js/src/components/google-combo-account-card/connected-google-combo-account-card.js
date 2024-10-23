/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '../account-card';
import AccountCardDescription, { Indicator } from './account-card-description';
import AppSpinner from '../app-spinner';
import useAutoCreateAdsMCAccounts from '.~/hooks/useAutoCreateAdsMCAccounts';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import './connected-google-combo-account-card.scss';

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 */
const ConnectedGoogleComboAccountCard = () => {
	const {
		googleAdsAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentAdsAccount,
	} = useGoogleAdsAccount();

	const {
		googleMCAccount,
		hasFinishedResolution: hasFinishedResolutionForCurrentMCAccount,
	} = useGoogleMCAccount();

	const { accountsCreated, hasDetermined, creatingWhich } =
		useAutoCreateAdsMCAccounts();

	const wasCreatingAccounts = useRef( null );
	const accountDetailsResolved =
		hasFinishedResolutionForCurrentAdsAccount &&
		hasFinishedResolutionForCurrentMCAccount;

	useEffect( () => {
		if ( creatingWhich ) {
			wasCreatingAccounts.current = creatingWhich;
		}

		if (
			wasCreatingAccounts.current &&
			accountsCreated &&
			accountDetailsResolved
		) {
			const accountsReady =
				!! googleAdsAccount?.id && !! googleMCAccount?.id;

			if ( accountsReady ) {
				wasCreatingAccounts.current = null;
			}
		}
	}, [
		accountDetailsResolved,
		accountsCreated,
		creatingWhich,
		googleAdsAccount,
		googleMCAccount,
	] );

	if (
		! accountsCreated &&
		( ! hasDetermined ||
			! hasFinishedResolutionForCurrentAdsAccount ||
			! hasFinishedResolutionForCurrentMCAccount )
	) {
		return <AccountCard description={ <AppSpinner /> } />;
	}

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE }
			alignIcon="top"
			className="gla-google-combo-account-card--connected"
			helper={
				<AccountCardDescription
					creatingAccounts={ wasCreatingAccounts.current }
				/>
			}
			indicator={
				<Indicator creatingAccounts={ wasCreatingAccounts.current } />
			}
		/>
	);
};

export default ConnectedGoogleComboAccountCard;
