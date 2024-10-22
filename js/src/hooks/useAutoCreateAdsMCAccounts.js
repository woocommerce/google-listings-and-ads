/**
 * External dependencies
 */
import { useEffect, useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCreateMCAccount from './useCreateMCAccount';
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';
import useShouldCreateAdsAccount from './useShouldCreateAdsAccount';
import useShouldCreateMCAccount from './useShouldCreateMCAccount';
import {
	CREATING_ADS_ACCOUNT,
	CREATING_BOTH_ACCOUNTS,
	CREATING_MC_ACCOUNT,
} from '.~/components/google-combo-account-card/constants';

const useAutoCreateAdsMCAccounts = () => {
	const lockedRef = useRef( false );
	// Create separate states.
	const [ accountsCreated, setAccountsCreated ] = useState( false );
	const [ creatingWhich, setCreatingWhich ] = useState( null );
	const [ hasDetermined, setDetermined ] = useState( false );

	const shouldCreateAds = useShouldCreateAdsAccount();
	const shouldCreateMC = useShouldCreateMCAccount();

	const [ handleCreateAccount, { response } ] = useCreateMCAccount();
	const [ upsertAdsAccount, { loading } ] = useUpsertAdsAccount();

	useEffect( () => {
		if (
			shouldCreateMC === null ||
			shouldCreateAds === null ||
			accountsCreated
		) {
			return;
		}

		if ( lockedRef.current && !! creatingWhich ) {
			const mcAccountCreated = !! response?.status;

			const resetState =
				( creatingWhich === CREATING_ADS_ACCOUNT && ! loading ) ||
				( creatingWhich === CREATING_MC_ACCOUNT && mcAccountCreated ) ||
				( creatingWhich === CREATING_BOTH_ACCOUNTS &&
					mcAccountCreated &&
					! loading );

			if ( resetState ) {
				lockedRef.current = false;
				setAccountsCreated( true );
				setCreatingWhich( null );
			}

			return;
		}

		let which = null;

		lockedRef.current = true;

		if ( shouldCreateMC && shouldCreateAds ) {
			which = CREATING_BOTH_ACCOUNTS;
		} else if ( shouldCreateMC ) {
			which = CREATING_MC_ACCOUNT;
		} else if ( shouldCreateAds ) {
			which = CREATING_ADS_ACCOUNT;
		}

		setCreatingWhich( which );
		setDetermined( true );

		if ( which ) {
			const handleCreateAccountCallback = async () => {
				if ( which === CREATING_BOTH_ACCOUNTS ) {
					await handleCreateAccount();
					await upsertAdsAccount();
				} else if ( which === CREATING_MC_ACCOUNT ) {
					await handleCreateAccount();
				} else if ( which === CREATING_ADS_ACCOUNT ) {
					await upsertAdsAccount();
				}
			};

			handleCreateAccountCallback();
			setCreatingWhich( which );
		}
	}, [
		accountsCreated,
		creatingWhich,
		handleCreateAccount,
		loading,
		response?.status,
		shouldCreateAds,
		shouldCreateMC,
		upsertAdsAccount,
	] );

	return {
		accountsCreated,
		hasDetermined,
		creatingWhich,
	};
};

export default useAutoCreateAdsMCAccounts;
