/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';
import { useState, useCallback, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import CreateAccountButton from './create-account-button';
import { useAppDispatch } from '.~/data';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import getWindowFeatures from '.~/utils/getWindowFeatures';

const ClaimTermsAndCreateAccountButton = ( { onCreateAccount = noop } ) => {
	const { createNotice } = useDispatchCoreNotices();
	const { fetchGoogleAdsAccount, fetchGoogleAdsAccountStatus } =
		useAppDispatch();
	const [ fetchAccountLoading, setFetchAccountLoading ] = useState( false );
	const [ upsertAdsAccount, { loading: createLoading } ] =
		useUpsertAdsAccount();
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

export default ClaimTermsAndCreateAccountButton;
