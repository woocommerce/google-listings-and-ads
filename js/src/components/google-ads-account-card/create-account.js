/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';
import { useState, Fragment, useCallback, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppButton from '.~/components/app-button';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import CreateAccountButton from './create-account-button';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import { useAppDispatch } from '.~/data';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import ClaimAccount from './claim-account';
import ClaimAccountModal from './claim-account-modal';
import getWindowFeatures from '.~/utils/getWindowFeatures';

const ClaimTermsAndCreateAccountButton = ( { onCreateAccount = noop } ) => {
	const { createNotice } = useDispatchCoreNotices();
	const { fetchGoogleAdsAccount, fetchGoogleAdsAccountStatus } =
		useAppDispatch();
	const [ fetchAccountLoading, setFetchAccountLoading ] = useState( false );
	const [ upsertAdsAccount, { loading: createLoading } ] =
		useUpsertAdsAccount();
	const { google } = useGoogleAccount();
	const { inviteLink } = useGoogleAdsAccountStatus();
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { hasAccess, step } = useGoogleAdsAccountStatus();

	const upsertAccount = useCallback( async () => {
		try {
			await upsertAdsAccount( { parse: false } );
		} catch ( e ) {
			// for status code 428, we want to allow users to continue and proceed,
			// so we swallow the error for status code 428,
			// and only display error message and exit this function for non-428 error.
			if ( e.status !== 428 ) {
				createNotice(
					'error',
					__(
						'Unable to create Google Ads account. Please try again later.',
						'google-listings-and-ads'
					)
				);
				return;
			}
		}

		setFetchAccountLoading( true );
		await fetchGoogleAdsAccountStatus();
		await fetchGoogleAdsAccount();
		setFetchAccountLoading( false );
	}, [
		createNotice,
		fetchGoogleAdsAccount,
		fetchGoogleAdsAccountStatus,
		upsertAdsAccount,
	] );

	const handleCreateAccount = async () => {
		await upsertAccount();
		onCreateAccount();
	};

	useEffect( () => {
		// Continue the setup process only when we are at the conversion_action step
		if ( hasAccess === true && step === 'conversion_action' ) {
			upsertAccount();
		}
	}, [ hasAccess, upsertAccount, step ] );

	const handleClaimAccountClick = useCallback(
		( event ) => {
			const { defaultView } = event.target.ownerDocument;
			const features = getWindowFeatures( defaultView, 600, 800 );

			defaultView.open( inviteLink, '_blank', features );
		},
		[ inviteLink ]
	);

	if ( ! google || google.active !== 'yes' ) {
		return null;
	}

	const shouldClaimGoogleAdsAccount = Boolean(
		googleAdsAccount.id && hasAccess === false
	);

	if ( shouldClaimGoogleAdsAccount ) {
		return (
			<AppButton isSecondary onClick={ handleClaimAccountClick }>
				{ __( 'Claim Account', 'google-listings-and-ads' ) }
			</AppButton>
		);
	}

	return (
		<CreateAccountButton
			loading={ createLoading || fetchAccountLoading }
			onCreateAccount={ handleCreateAccount }
		/>
	);
};

const CreateAccount = ( props ) => {
	const { allowShowExisting, onShowExisting } = props;
	const [ showClaimModal, setShowClaimModal ] = useState( false );
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { hasAccess } = useGoogleAdsAccountStatus();

	const shouldClaimGoogleAdsAccount = Boolean(
		googleAdsAccount.id && hasAccess === false
	);

	const handleOnRequestClose = () => {
		setShowClaimModal( false );
	};

	const handleOnCreateAccount = () => {
		setShowClaimModal( true );
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_ADS }
			alignIcon="top"
			indicator={
				<ClaimTermsAndCreateAccountButton
					onCreateAccount={ handleOnCreateAccount }
				/>
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
