/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';
import { useState, useCallback, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CreateAccountButton from './create-account-button';
import { useAppDispatch } from '.~/data';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';

const ClaimTermsAndCreateAccountButton = ( { onCreateAccount = noop } ) => {
	const { createNotice } = useDispatchCoreNotices();
	const { fetchGoogleAdsAccount, fetchGoogleAdsAccountStatus } =
		useAppDispatch();
	const [ fetchAccountLoading, setFetchAccountLoading ] = useState( false );
	const [ upsertAdsAccount, { loading: createLoading } ] =
		useUpsertAdsAccount();
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

	return (
		<CreateAccountButton
			loading={ createLoading || fetchAccountLoading }
			onCreateAccount={ handleCreateAccount }
		/>
	);
};

export default ClaimTermsAndCreateAccountButton;
