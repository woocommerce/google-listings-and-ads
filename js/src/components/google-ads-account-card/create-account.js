/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppButton from '.~/components/app-button';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';
import ClaimAccount from './claim-account';
import ClaimAccountModal from './claim-account-modal';
import CreateAccountButton from './create-account-button';
import ClaimAccountButton from './claim-account-button';

const CreateAccount = ( props ) => {
	const { allowShowExisting, onShowExisting } = props;
	const [ showClaimModal, setShowClaimModal ] = useState( false );
	const {
		googleAdsAccount,
		hasFinishedResolution: hasFinishedAdsAccountResolution,
	} = useGoogleAdsAccount();
	const {
		hasAccess,
		step,
		hasFinishedResolution: hasFinishedAdsStatusResolution,
	} = useGoogleAdsAccountStatus();
	const [ upsertAccount, { loading: isAdsCreateAccountLoading } ] =
		useUpsertAdsAccount();
	const shouldClaimGoogleAdsAccount = Boolean(
		googleAdsAccount.id && hasAccess === false
	);

	const handleOnRequestClose = () => {
		setShowClaimModal( false );
	};

	const handleOnCreateAccount = () => {
		upsertAccount();
		setShowClaimModal( true );
	};

	const isLoading =
		isAdsCreateAccountLoading ||
		! ( hasFinishedAdsAccountResolution && hasFinishedAdsStatusResolution );

	useEffect( () => {
		// Continue the setup process only when we are at the conversion_action step
		if ( hasAccess === true && step === 'conversion_action' ) {
			upsertAccount();
		}
	}, [ hasAccess, upsertAccount, step ] );

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_ADS }
			alignIcon="top"
			indicator={
				shouldClaimGoogleAdsAccount ? (
					<ClaimAccountButton />
				) : (
					<CreateAccountButton
						onCreateAccount={ handleOnCreateAccount }
						loading={ isLoading }
					/>
				)
			}
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
