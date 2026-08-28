/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import ClaimAccount from './claim-account';
import ClaimAccountButton from './claim-account-button';
import ClaimAccountModal from './claim-account-modal';
import CreateAccountButton from './create-account-button';
import Section from '~/components/section';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useGoogleAdsAccountStatus from '~/hooks/useGoogleAdsAccountStatus';
import useUpsertAdsAccount from '~/hooks/useUpsertAdsAccount';

const CreateAccount = ( props ) => {
	const { allowShowExisting, onShowExisting } = props;
	const [ showClaimModal, setShowClaimModal ] = useState( false );
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { hasAccess, step } = useGoogleAdsAccountStatus();
	const [ upsertAdsAccount, { loading, action } ] = useUpsertAdsAccount();

	const shouldClaimGoogleAdsAccount = Boolean(
		googleAdsAccount.id && hasAccess === false
	);

	const handleOnRequestClose = () => {
		setShowClaimModal( false );
	};

	const handleOnCreateAccount = async () => {
		await upsertAdsAccount();
		setShowClaimModal( true );
	};

	// Update the Ads account once we have access.
	useEffect( () => {
		if ( hasAccess === true && step === 'conversion_action' ) {
			upsertAdsAccount();
		}
	}, [ hasAccess, upsertAdsAccount, step ] );

	const getIndicator = () => {
		if ( loading ) {
			const text =
				action === 'create'
					? __( 'Creating…', 'google-listings-and-ads' )
					: __( 'Updating…', 'google-listings-and-ads' );

			return <AppButton text={ text } loading />;
		}

		if ( shouldClaimGoogleAdsAccount ) {
			return (
				<ClaimAccountButton isSecondary>
					{ __( 'Claim Account', 'google-listings-and-ads' ) }
				</ClaimAccountButton>
			);
		}

		return (
			<CreateAccountButton onCreateAccount={ handleOnCreateAccount } />
		);
	};

	return (
		<AccountCard
			alignIcon="top"
			appearance={ APPEARANCE.GOOGLE_ADS }
			indicator={ getIndicator() }
		>
			{ allowShowExisting && ! shouldClaimGoogleAdsAccount && (
				<Section.Card.Footer>
					<AppButton
						disabled={ loading }
						onClick={ onShowExisting }
						isLink
					>
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
