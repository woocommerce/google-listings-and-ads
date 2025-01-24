/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectAds from './connect-ads';
import AccountDetails from './account-details';
import ConnectedAdsAccountDetail from './connected-ads-account-detail';
import Indicator from './indicator';
import getAccountCreationTexts from './getAccountCreationTexts';
import SpinnerCard from '~/components/spinner-card';
import { StoreAddressCard } from '~/components/contact-information';
import useAutoCreateAdsMCAccounts from '~/hooks/useAutoCreateAdsMCAccounts';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import useExistingGoogleMCAccounts from '~/hooks/useExistingGoogleMCAccounts';
import useCreateMCAccount from '~/hooks/useCreateMCAccount';
import {
	ConnectMC,
	WPComAppAuthorizationCard,
} from '~/components/google-mc-account-card';
import useExistingGoogleAdsAccounts from '~/hooks/useExistingGoogleAdsAccounts';
import AppButton from '~/components/app-button';
import { SwitchAccountButton } from '~/components/google-account-card';
import useGoogleAdsAccountStatus from '~/hooks/useGoogleAdsAccountStatus';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useUpsertAdsAccount from '~/hooks/useUpsertAdsAccount';
import showAdsConversionNotice from '~/utils/showAdsConversionNotice';
import './connected-google-combo-account-card.scss';

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads and Merchant Center account creation if the user does not have accounts.
 */
const ConnectedGoogleComboAccountCard = () => {
	const [ editMode, setEditMode ] = useState( false );

	// We use a single instance of the hook to create a MC (Merchant Center) account,
	// ensuring consistent results across both the main component (ConnectedGoogleComboAccountCard) and its child component (ConnectMC).
	// This approach is especially useful when an MC account is automatically created, and the URL needs to be reclaimed.
	// The URL reclaim component is rendered within the ConnectMC component.
	const [ createMCAccount, resultCreateMCAccount ] = useCreateMCAccount();
	const { data: existingGoogleMCAccounts } = useExistingGoogleMCAccounts();
	const { hasDetermined, creatingWhich } =
		useAutoCreateAdsMCAccounts( createMCAccount );
	const { text, subText } = getAccountCreationTexts( creatingWhich );
	const { existingAccounts: existingGoogleAdsAccounts } =
		useExistingGoogleAdsAccounts();
	const {
		isReady: isGoogleMCReady,
		hasGoogleMCConnection,
		hasFinishedResolution,
	} = useGoogleMCAccount();
	const { invalidateResolution } = useAppDispatch();
	const { googleAdsAccount, hasGoogleAdsConnection } = useGoogleAdsAccount();
	const { hasAccess, step } = useGoogleAdsAccountStatus();
	const [ upsertAdsAccount, { action, loading } ] = useUpsertAdsAccount();

	const hasExistingGoogleMCAccounts = existingGoogleMCAccounts?.length > 0;
	const hasExistingGoogleAdsAccounts = existingGoogleAdsAccounts?.length > 0;
	const shouldClaimGoogleAdsAccount = Boolean(
		! loading && googleAdsAccount?.id && hasAccess === false
	);
	const finalizeAdsAccountCreation =
		hasAccess === true && step === 'conversion_action';

	// Ideally updating the account should be done in ConnectMC component but the latter is not always rendered,
	// (for e.g when the user is creating the first account).
	useEffect( () => {
		const upsertAccount = async () => {
			if ( finalizeAdsAccountCreation ) {
				await upsertAdsAccount();
				invalidateResolution( 'getExistingGoogleAdsAccounts', [] );
			}
		};

		upsertAccount();
	}, [ finalizeAdsAccountCreation, upsertAdsAccount, invalidateResolution ] );

	const handleCancelClick = () => {
		setEditMode( false );
	};

	const handleEditClick = () => {
		setEditMode( true );
	};

	// During MC account creation, we need to show the ConnectMC component
	// when the account creation needs to show the Switch or Reclaim flow.
	const googleMCHasError = [ 409, 403 ].includes(
		resultCreateMCAccount.response?.status
	);

	// After creating a new account, it may be connected but not ready
	// (e.g., needing to reclaim the URL). In this case, we show the ConnectMC
	// component, even if the existing accounts list has not yet updated.
	//
	// The last `hasGoogleMCConnection` condition exists for the scenario with these steps:
	// 1. Automatically creating a Google Merchant Center account and reclaiming URL is required.
	// 2. The merchant interrupts the onboarding flow and then resumes it.
	//    This is also the case for refreshing webpage.
	// 3. The newly created account is not yet among the existing accounts.
	// 4. The condition enables the merchant to resume the Google Merchant Center connection
	//    from the step of connecting the newly created account
	const canShowConnectMC =
		googleMCHasError ||
		hasExistingGoogleMCAccounts ||
		hasGoogleMCConnection;
	const showConnectMC = canShowConnectMC && ( editMode || ! isGoogleMCReady );

	// After creating a new account, it may not show up in the existing accounts list
	// immediately. In this case, we show the ConnectAds component in edit mode unless
	// we're showing the claim notice in the upper card.
	const canShowConnectAds =
		hasGoogleAdsConnection || hasExistingGoogleAdsAccounts;
	const showConnectAds =
		canShowConnectAds && ( editMode || ! hasGoogleAdsConnection );

	// When Ads and MC are disconnected in edit mode, exit edit mode.
	useEffect( () => {
		if ( editMode && ! hasGoogleMCConnection && ! hasGoogleAdsConnection ) {
			setEditMode( false );
		}
	}, [ editMode, hasGoogleAdsConnection, hasGoogleMCConnection ] );

	if ( ! hasDetermined ) {
		return <SpinnerCard />;
	}

	const switchAccountButton = (
		<SwitchAccountButton
			isTertiary
			text={ __(
				'Or, connect to a different Google account',
				'google-listings-and-ads'
			) }
		/>
	);

	const getCardActions = () => {
		if ( editMode ) {
			return (
				<div className="gla-google-combo-account-card__description-actions">
					{ switchAccountButton }
					<AppButton isTertiary onClick={ handleCancelClick }>
						{ __( 'Cancel', 'google-listings-and-ads' ) }
					</AppButton>
				</div>
			);
		}

		// When not in edit mode, only show the edit button if clicking the
		// button would change the visibility of the ConnectAds or ConnectMC cards.
		return (
			<div className="gla-google-combo-account-card__description-actions">
				{ ( showConnectAds || ! canShowConnectAds ) &&
				( showConnectMC || ! canShowConnectMC ) ? (
					switchAccountButton
				) : (
					<AppButton
						isTertiary
						text={ __( 'Edit', 'google-listings-and-ads' ) }
						onClick={ handleEditClick }
					/>
				) }
			</div>
		);
	};

	// Show the spinner if there's an account creation in progress and account should not be claimed.
	// If we are not showing the ConnectMC screen, for e.g when we are creating the first account,
	// then show the spinner in the Google combo card while the Ads account is being claimed.
	const showSpinner =
		( Boolean( creatingWhich ) && ! shouldClaimGoogleAdsAccount ) ||
		( ! showConnectAds && finalizeAdsAccountCreation );

	const showConversionMeasurementNotice =
		showAdsConversionNotice( googleAdsAccount );

	const isGoogleMCResolvedAndReady = hasFinishedResolution && isGoogleMCReady;

	return (
		<div className="gla-google-combo-account-card-wrapper">
			<AccountCard
				appearance={ APPEARANCE.GOOGLE }
				alignIcon="top"
				className="gla-google-combo-account-card gla-google-combo-account-card--connected gla-google-combo-service-account-card--google"
				description={ text || <AccountDetails /> }
				actions={ getCardActions() }
				helper={ subText }
				indicator={ <Indicator showSpinner={ showSpinner } /> }
				detail={
					<ConnectedAdsAccountDetail
						showConversionMeasurementNotice={
							showConversionMeasurementNotice
						}
						claimGoogleAdsAccount={ shouldClaimGoogleAdsAccount }
					/>
				}
				expandedDetail
			/>

			{ showConnectAds && (
				<ConnectAds
					onRequestCreate={ upsertAdsAccount }
					upsertingAction={ action }
				/>
			) }

			{ showConnectMC && (
				<ConnectMC
					createAccount={ createMCAccount }
					resultCreateAccount={ resultCreateMCAccount }
					className="gla-google-combo-account-card gla-google-combo-service-account-card--mc"
				/>
			) }
			{ isGoogleMCResolvedAndReady && <WPComAppAuthorizationCard /> }
			{ isGoogleMCResolvedAndReady && <StoreAddressCard /> }
		</div>
	);
};

export default ConnectedGoogleComboAccountCard;
