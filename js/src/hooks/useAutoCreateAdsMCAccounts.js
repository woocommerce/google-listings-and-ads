/**
 * External dependencies
 */
import { useEffect, useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useGoogleMCAccount from './useGoogleMCAccount';
import useExistingGoogleMCAccounts from './useExistingGoogleMCAccounts';
import useUpsertAdsAccount from '~/hooks/useUpsertAdsAccount';
import useShouldCreateAdsAccount from './useShouldCreateAdsAccount';
import {
	CREATING_ADS_ACCOUNT,
	CREATING_BOTH_ACCOUNTS,
	CREATING_MC_ACCOUNT,
} from '~/components/google-combo-account-card/constants';
import { ERROR_SLOTS } from '~/data/constants';
import { useAppDispatch } from '~/data';

const useShouldCreateMCAccount = () => {
	const {
		hasFinishedResolution: hasResolvedAccount,
		hasGoogleMCConnection: hasConnection,
	} = useGoogleMCAccount();

	const {
		hasFinishedResolution: hasResolvedExistingAccounts,
		data: accounts,
	} = useExistingGoogleMCAccounts();

	// Return null if the account hasn't been resolved or the existing accounts haven't been resolved
	if ( ! hasResolvedAccount || ! hasResolvedExistingAccounts ) {
		return null;
	}

	return ! hasConnection && accounts?.length === 0;
};

/**
 * @typedef {Object} AutoCreateAdsMCAccountsData
 * @property {boolean} hasDetermined - Whether the checks to determine if accounts should be created are finished.
 * @property {('ads'|'mc'|'both'|null)} creatingWhich - Which accounts are being created ('ads', 'mc', 'both'), or `null` if none.
 */

/**
 * useAutoCreateAdsMCAccounts hook.
 * Creates Google Ads and Merchant Center accounts if the user doesn't have any existing and connected accounts.
 * @param {Function} createMCAccount Callback to create a Merchant Center account.
 * @return {AutoCreateAdsMCAccountsData} Object containing account creation data.
 */
const useAutoCreateAdsMCAccounts = ( createMCAccount ) => {
	const lockedRef = useRef( false );
	const [ creatingWhich, setCreatingWhich ] = useState( null );
	const [ hasDetermined, setDetermined ] = useState( false );

	const shouldCreateAds = useShouldCreateAdsAccount();
	const shouldCreateMC = useShouldCreateMCAccount();
	const [ upsertAdsAccount ] = useUpsertAdsAccount();
	const { clearDetailedErrorBySlots } = useAppDispatch();
	const {
		GOOGLE_ADS_CONNECTION_ERROR_SLOT,
		GOOGLE_MC_CONNECTION_ERROR_SLOT,
	} = ERROR_SLOTS;

	useEffect( () => {
		if (
			// Wait for all determinations to be ready
			shouldCreateMC === null ||
			shouldCreateAds === null ||
			// Avoid repeated calls
			lockedRef.current
		) {
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
					const slotsToClear = [
						GOOGLE_ADS_CONNECTION_ERROR_SLOT,
						GOOGLE_MC_CONNECTION_ERROR_SLOT,
					];
					clearDetailedErrorBySlots( slotsToClear );

					await createMCAccount();
					await upsertAdsAccount();
				} else if ( which === CREATING_MC_ACCOUNT ) {
					const slotsToClear = [ GOOGLE_MC_CONNECTION_ERROR_SLOT ];
					clearDetailedErrorBySlots( slotsToClear );

					await createMCAccount();
				} else if ( which === CREATING_ADS_ACCOUNT ) {
					const slotsToClear = [ GOOGLE_ADS_CONNECTION_ERROR_SLOT ];
					clearDetailedErrorBySlots( slotsToClear );

					await upsertAdsAccount();
				}
				setCreatingWhich( null );
			};

			handleCreateAccountCallback();
		}
	}, [
		GOOGLE_ADS_CONNECTION_ERROR_SLOT,
		GOOGLE_MC_CONNECTION_ERROR_SLOT,
		clearDetailedErrorBySlots,
		createMCAccount,
		shouldCreateAds,
		shouldCreateMC,
		upsertAdsAccount,
	] );

	return {
		hasDetermined,
		creatingWhich,
	};
};

export default useAutoCreateAdsMCAccounts;
