/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';
import { useState, Fragment, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppButton from '.~/components/app-button';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import CreateAccountButton from './create-account-button';
import useGoogleAccountCheck from '.~/hooks/useGoogleAccountCheck';
import { useAppDispatch } from '.~/data';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import useShouldClaimGoogleAdsAccount from '.~/hooks/useShouldClaimGoogleAdsAccount';
import ClaimAccount from './claim-account';
import ClaimAccountModal from './claim-account-modal';
import getWindowFeatures from '.~/utils/getWindowFeatures';

const ClaimTermsAndCreateAccountButton = ( { onCreateAccount = noop } ) => {
	const { createNotice } = useDispatchCoreNotices();
	const { fetchGoogleAdsAccount } = useAppDispatch();
	const [ fetchAccountLoading, setFetchAccountLoading ] = useState( false );
	const [ upsertAdsAccount, { loading: createLoading } ] =
		useUpsertAdsAccount();
	const { google } = useGoogleAccountCheck();
	const { inviteLink } = useGoogleAdsAccountStatus();
	const { shouldClaimGoogleAdsAccount } = useShouldClaimGoogleAdsAccount();

	const handleCreateAccount = async () => {
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
		await fetchGoogleAdsAccount();
		onCreateAccount();
		setFetchAccountLoading( false );
	};

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
	const { allowShowExisting, onShowExisting, disabled } = props;

	const [ claimModalOpen, setClaimModalOpen ] = useState( false );
	const { shouldClaimGoogleAdsAccount } = useShouldClaimGoogleAdsAccount();

	const handleOnRequestClose = () => {
		setClaimModalOpen( false );
	};

	const handleOnCreateAccount = () => {
		setClaimModalOpen( true );
	};

	return (
		<AccountCard
			disabled={ disabled }
			appearance={ APPEARANCE.GOOGLE_ADS }
			alignIcon="top"
			indicator={
				<ClaimTermsAndCreateAccountButton
					disabled={ disabled }
					onCreateAccount={ handleOnCreateAccount }
				/>
			}
		>
			{ allowShowExisting && (
				<Section.Card.Footer>
					<AppButton
						isLink
						onClick={ onShowExisting }
						disabled={ disabled }
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
					{ claimModalOpen && (
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
