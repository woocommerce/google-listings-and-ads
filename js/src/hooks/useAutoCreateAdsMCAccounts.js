/**
 * External dependencies
 */
import { useEffect, useState, useRef, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCreateMCAccount from './useCreateMCAccount';
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useGoogleMCAccount from './useGoogleMCAccount';
import {
	CREATING_ADS_ACCOUNT,
	CREATING_BOTH_ACCOUNTS,
	CREATING_MC_ACCOUNT,
} from '.~/components/google-combo-account-card/constants';

/**
 * Hook to automatically create Ads and Merchant Center accounts if they do not exist.
 *
 * @return {Object} The state of the account creation process.
 * @property {boolean} hasFinishedResolutionForExistingAdsMCAccounts Indicates whether the checks for existing Merchant Center (MC) and Google Ads accounts have been completed.
 * @property {'ads'|'mc'|'both'|null} creatingWhichAccount The type of account that is being created.
 */
const useAutoCreateAdsMCAccounts = () => {
	// Create separate states.
	const [ accountsCreated, setAccountsCreated ] = useState( false );
	const [ creatingWhichAccount, setCreatingWhichAccount ] = useState( null );
	const shouldCreateAccounts = useRef();

	const {
		data: existingMCAccounts,
		hasFinishedResolution: hasFinishedResolutionForExistingMCAccounts,
	} = useExistingGoogleMCAccounts();

	const {
		existingAccounts: existingAdsAccounts,
		hasFinishedResolution: hasFinishedResolutionForExistingAdsAccounts,
	} = useExistingGoogleAdsAccounts();

	const {
		hasFinishedResolution: hasFinishedResolutionForGoogleAdsAccount,
		hasGoogleAdsConnection,
	} = useGoogleAdsAccount();

	const {
		hasGoogleMCConnection,
		hasFinishedResolution: hasFinishedResolutionForGoogleMCAccount,
	} = useGoogleMCAccount();

	const [ handleCreateAccount, { response } ] = useCreateMCAccount();
	const [ upsertAdsAccount, { loading } ] = useUpsertAdsAccount();

	const hasExistingMCAccount = existingMCAccounts?.length > 0;
	const hasExistingAdsAccount = existingAdsAccounts?.length > 0;

	const adsAccountCreationRequired =
		! hasGoogleAdsConnection && ! hasExistingAdsAccount;
	const MCAccountCreationRequired =
		! hasGoogleMCConnection && ! hasExistingMCAccount;

	const googleAdsAccountChecksResolved =
		hasFinishedResolutionForExistingAdsAccounts &&
		hasFinishedResolutionForGoogleAdsAccount;

	const googleMCAccountChecksResolved =
		hasFinishedResolutionForGoogleMCAccount &&
		hasFinishedResolutionForExistingMCAccounts;

	const accountCreationChecksResolved =
		googleAdsAccountChecksResolved && googleMCAccountChecksResolved;

	if ( accountCreationChecksResolved ) {
		if ( adsAccountCreationRequired || MCAccountCreationRequired ) {
			const createBothAccounts =
				adsAccountCreationRequired && MCAccountCreationRequired;

			if ( createBothAccounts ) {
				shouldCreateAccounts.current = CREATING_BOTH_ACCOUNTS;
			} else if ( adsAccountCreationRequired ) {
				shouldCreateAccounts.current = CREATING_ADS_ACCOUNT;
			} else {
				shouldCreateAccounts.current = CREATING_MC_ACCOUNT;
			}
		}
	}

	const handlePostAccountCreation = useCallback( () => {
		if ( ! creatingWhichAccount ) {
			return;
		}

		const mcAccountCreated = !! response?.status;

		const resetState =
			( creatingWhichAccount === CREATING_ADS_ACCOUNT && ! loading ) ||
			( creatingWhichAccount === CREATING_MC_ACCOUNT &&
				mcAccountCreated ) ||
			( creatingWhichAccount === CREATING_BOTH_ACCOUNTS &&
				mcAccountCreated &&
				! loading );

		if ( resetState ) {
			setAccountsCreated( true );
			setCreatingWhichAccount( null );
		}
	}, [ response, loading, creatingWhichAccount ] );

	const handleAccountCreation = useCallback( async () => {
		if (
			! accountCreationChecksResolved ||
			creatingWhichAccount ||
			accountsCreated
		) {
			return;
		}

		if ( shouldCreateAccounts.current ) {
			setCreatingWhichAccount( shouldCreateAccounts.current );

			if ( shouldCreateAccounts.current === CREATING_BOTH_ACCOUNTS ) {
				await handleCreateAccount();
				await upsertAdsAccount();
			} else if (
				shouldCreateAccounts.current === CREATING_ADS_ACCOUNT
			) {
				await upsertAdsAccount();
			} else {
				await handleCreateAccount();
			}
		}
	}, [
		accountCreationChecksResolved,
		creatingWhichAccount,
		accountsCreated,
		handleCreateAccount,
		upsertAdsAccount,
	] );

	useEffect( () => {
		handlePostAccountCreation();
	}, [ response, loading, handlePostAccountCreation ] );

	useEffect( () => {
		handleAccountCreation();
	}, [ handleAccountCreation ] );

	return {
		hasFinishedResolutionForExistingAdsMCAccounts:
			accountCreationChecksResolved,
		accountsCreated,
		creatingWhichAccount,
	};
};

export default useAutoCreateAdsMCAccounts;
