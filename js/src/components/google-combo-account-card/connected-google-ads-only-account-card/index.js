/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { SwitchAccountButton } from '~/components/google-account-card';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectAds from '../connect-ads';
import AccountDetails from '../account-details';
import ConnectedAdsAccountDetail from '../connected-ads-account-detail';
import Indicator from './indicator';
import getAccountCreationTexts from '../getAccountCreationTexts';
import SpinnerCard from '~/components/spinner-card';
import useAutoCreateAdsAccount from '~/hooks/useAutoCreateAdsAccount';
import useExistingGoogleAdsAccounts from '~/hooks/useExistingGoogleAdsAccounts';
import AppButton from '~/components/app-button';
import useGoogleAdsAccountStatus from '~/hooks/useGoogleAdsAccountStatus';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useUpsertAdsAccount from '~/hooks/useUpsertAdsAccount';
import showAdsConversionNotice from '~/utils/showAdsConversionNotice';
import './index.scss';

/**
 * Renders a Google account card UI with connected account information.
 * It will also kickoff Ads account creation if the user does not have an Ads account.
 */
const ConnectedGoogleAdsOnlyAccountCard = () => {
	const [ editMode, setEditMode ] = useState( false );
	const { hasDetermined, creatingWhich } = useAutoCreateAdsAccount();
	const { text, subText } = getAccountCreationTexts( creatingWhich );
	const { existingAccounts: existingGoogleAdsAccounts } =
		useExistingGoogleAdsAccounts();
	const { invalidateResolution } = useAppDispatch();
	const { googleAdsAccount, hasGoogleAdsConnection } = useGoogleAdsAccount();
	const { hasAccess, step } = useGoogleAdsAccountStatus();
	const [ upsertAdsAccount, { action, loading } ] = useUpsertAdsAccount();

	const hasExistingGoogleAdsAccounts = existingGoogleAdsAccounts?.length > 0;
	const shouldClaimGoogleAdsAccount = Boolean(
		! loading && googleAdsAccount?.id && hasAccess === false
	);
	const finalizeAdsAccountCreation =
		hasAccess === true && step === 'conversion_action';

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

	// After creating a new account, it may not show up in the existing accounts list
	// immediately. In this case, we show the ConnectAds component in edit mode unless
	// we're showing the claim notice in the upper card.
	const canShowConnectAds =
		hasGoogleAdsConnection || hasExistingGoogleAdsAccounts;
	const showConnectAds =
		canShowConnectAds && ( editMode || ! hasGoogleAdsConnection );

	// When Ads account is disconnected in edit mode, exit edit mode.
	useEffect( () => {
		if ( editMode && ! hasGoogleAdsConnection ) {
			setEditMode( false );
		}
	}, [ editMode, hasGoogleAdsConnection ] );

	if ( ! hasDetermined ) {
		return <SpinnerCard />;
	}

	const switchAccountButton = (
		<SwitchAccountButton
			text={ __(
				'Or, connect to a different Google account',
				'google-listings-and-ads'
			) }
			isTertiary
		/>
	);

	const getCardActions = () => {
		if ( editMode ) {
			return (
				<div className="gla-connected-google-ads-only-account-card__description-actions">
					{ switchAccountButton }
					<AppButton onClick={ handleCancelClick } isTertiary>
						{ __( 'Cancel', 'google-listings-and-ads' ) }
					</AppButton>
				</div>
			);
		}

		// When not in edit mode, only show the edit button if clicking the
		// button would change the visibility of the ConnectAds card.
		return (
			<div className="gla-connected-google-ads-only-account-card__description-actions">
				{ showConnectAds || ! canShowConnectAds ? (
					switchAccountButton
				) : (
					<AppButton
						onClick={ handleEditClick }
						text={ __( 'Edit', 'google-listings-and-ads' ) }
						isTertiary
					/>
				) }
			</div>
		);
	};

	// Show the spinner if there's an account creation in progress and account should not be claimed.
	// If account is being claimed, then show the spinner in the Google combo card.
	const showSpinner =
		( Boolean( creatingWhich ) && ! shouldClaimGoogleAdsAccount ) ||
		( ! showConnectAds && finalizeAdsAccountCreation );

	const showConversionMeasurementNotice =
		showAdsConversionNotice( googleAdsAccount );

	return (
		<div className="gla-connected-google-ads-only-account-card-wrapper">
			<AccountCard
				actions={ getCardActions() }
				alignIcon="top"
				appearance={ APPEARANCE.GOOGLE }
				className="gla-connected-google-ads-only-account-card gla-connected-google-ads-only-account-card--connected gla-connected-google-ads-only-account-card--google"
				description={ text || <AccountDetails /> }
				detail={
					<ConnectedAdsAccountDetail
						claimGoogleAdsAccount={ shouldClaimGoogleAdsAccount }
						showConversionMeasurementNotice={
							showConversionMeasurementNotice
						}
					/>
				}
				helper={ subText }
				indicator={ <Indicator showSpinner={ showSpinner } /> }
				expandedDetail
			/>

			{ showConnectAds && (
				<ConnectAds
					onRequestCreate={ upsertAdsAccount }
					upsertingAction={ action }
				/>
			) }
		</div>
	);
};

export default ConnectedGoogleAdsOnlyAccountCard;
