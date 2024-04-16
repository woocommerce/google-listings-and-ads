/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import ClaimAccount from './claim-account';
import ClaimAccountButton from './claim-account-button';
import ClaimAccountModal from './claim-account-modal';
import CreateAccountButton from './create-account-button';
import Section from '.~/wcdl/section';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';
import useWindowFocusCallbackIntervalEffect from '.~/hooks/useWindowFocusCallbackIntervalEffect';
import LoadingLabel from '../loading-label/loading-label';

const CreateAccount = ( props ) => {
	const { allowShowExisting, onShowExisting } = props;
	const [ showClaimModal, setShowClaimModal ] = useState( false );
	const [ isClaiming, setIsClaiming ] = useState( false );
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { hasAccess, step } = useGoogleAdsAccountStatus();
	const [ upsertAdsAccount, { loading: isUpsertAdsAccountLoading } ] =
		useUpsertAdsAccount();
	const shouldClaimGoogleAdsAccount = Boolean(
		googleAdsAccount.id && hasAccess === false
	);

	const handleOnRequestClose = () => {
		setShowClaimModal( false );
		setIsClaiming( false );
	};

	const handleOnCreateAccount = async () => {
		setIsClaiming( true );
		await upsertAdsAccount();
		setShowClaimModal( true );
	};

	const handleOnClaimClick = () => {
		setIsClaiming( true );
	};

	const { fetchGoogleAdsAccountStatus } = useAppDispatch();

	const refreshAdsStatus = useCallback( async () => {
		if ( ! shouldClaimGoogleAdsAccount ) {
			return false;
		}

		await fetchGoogleAdsAccountStatus();
	}, [ fetchGoogleAdsAccountStatus, shouldClaimGoogleAdsAccount ] );

	useWindowFocusCallbackIntervalEffect( refreshAdsStatus, 30 );

	// Update the Ads account once we have access.
	useEffect( () => {
		if ( hasAccess === true && step === 'conversion_action' ) {
			setIsClaiming( false );
			upsertAdsAccount();
		}
	}, [ hasAccess, upsertAdsAccount, step ] );

	const getIndicator = () => {
		if ( shouldClaimGoogleAdsAccount ) {
			return (
				<ClaimAccountButton
					loading={ isClaiming || showClaimModal }
					onClaimClick={ handleOnClaimClick }
				/>
			);
		}

		return googleAdsAccount.id && isUpsertAdsAccountLoading ? (
			<LoadingLabel
				text={ __( 'Updating…', 'google-listings-and-ads' ) }
			/>
		) : (
			<CreateAccountButton
				onCreateAccount={ handleOnCreateAccount }
				loading={ isUpsertAdsAccountLoading }
			/>
		);
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_ADS }
			alignIcon="top"
			indicator={ getIndicator() }
		>
			{ allowShowExisting && ! shouldClaimGoogleAdsAccount && (
				<Section.Card.Footer>
					<AppButton isLink onClick={ onShowExisting }>
						{ __(
							'Or, use your existing Google Ads account',
							'google-listings-and-ads'
						) }
					</AppButton>
				</Section.Card.Footer>
			) }

			{ shouldClaimGoogleAdsAccount && (
				<Fragment>
					{ showClaimModal && (
						<ClaimAccountModal
							onRequestClose={ handleOnRequestClose }
						/>
					) }

					<ClaimAccount />
				</Fragment>
			) }
		</AccountCard>
	);
};

export default CreateAccount;
