/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCreateMCAccount from './useCreateMCAccount';
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useGoogleMCAccount from './useGoogleMCAccount';
import { GOOGLE_MC_ACCOUNT_STATUS } from '.~/constants';

/**
 * Custom hook to handle the creation of Google Merchant Center (MC) and Google Ads accounts.
 *
 * @typedef {Object} AutoCreateAccountsStatus
 * @property {boolean} accountsCreated Indicates if both the Google Ads and Google Merchant Center accounts have been successfully created.
 * @property {boolean} accountCreationChecksResolved Indicates if the account creation checks (for existing accounts) have been resolved.
 * @property {boolean} isCreatingAccounts Indicates if the Google Ads and/or Google Merchant Center account(s) are being created.
 * @property {boolean} isCreatingOnlyAdsAccount Indicates if only the Google Ads account is currently being created.
 * @property {boolean} isCreatingBothAccounts Indicates if both the Google Ads and Google Merchant Center accounts are currently being created.
 * @property {boolean} isCreatingOnlyMCAccount Indicates if only the Google Merchant Center account is currently being created.
 *
 * @return {AutoCreateAccountsStatus} Object containing properties related to the account creation status.
 */
const useAutoCreateAdsMCAccounts = () => {
	// Refs are used to avoid the re-render of the parent component.
	const isCreatingBothAccountsRef = useRef( false );
	const isCreatingAdsAccountRef = useRef( false );
	const isCreatingMCAccountRef = useRef( false );
	const initHasExistingMCAccountsRef = useRef( null );
	const initHasExistingAdsAccountsRef = useRef( null );
	const accountsCreatedRef = useRef( false );

	const {
		data: existingMCAccounts,
		hasFinishedResolution: hasFinishedResolutionForExistingMCAccounts,
	} = useExistingGoogleMCAccounts();

	const {
		existingAccounts: existingAdsAccounts,
		hasFinishedResolution: hasFinishedResolutionForExistingAdsAccount,
	} = useExistingGoogleAdsAccounts();

	const {
		hasFinishedResolution: hasFinishedResolutionForGoogleAdsAccount,
		hasGoogleAdsConnection,
	} = useGoogleAdsAccount();

	const {
		googleMCAccount,
		hasFinishedResolution: hasFinishedResolutionForGoogleMCAccount,
	} = useGoogleMCAccount();

	const [ handleCreateAccount, { response } ] = useCreateMCAccount();
	const [ upsertAdsAccount, { loading } ] = useUpsertAdsAccount();
	const isGoogleMCConnected =
		googleMCAccount?.status === GOOGLE_MC_ACCOUNT_STATUS.CONNECTED ||
		googleMCAccount?.status === GOOGLE_MC_ACCOUNT_STATUS.INCOMPLETE;

	const hasExistingMCAccount =
		isGoogleMCConnected || existingMCAccounts?.length > 0;
	const hasExistingAdsAccount =
		hasGoogleAdsConnection || existingAdsAccounts?.length > 0;

	if (
		initHasExistingMCAccountsRef.current === null &&
		hasFinishedResolutionForExistingMCAccounts &&
		hasFinishedResolutionForGoogleMCAccount
	) {
		initHasExistingMCAccountsRef.current = hasExistingMCAccount;
	}

	if (
		initHasExistingAdsAccountsRef.current === null &&
		hasFinishedResolutionForExistingAdsAccount &&
		hasFinishedResolutionForGoogleAdsAccount
	) {
		initHasExistingAdsAccountsRef.current = hasExistingAdsAccount;
	}

	const accountCreationChecksResolved =
		initHasExistingAdsAccountsRef.current !== null &&
		initHasExistingMCAccountsRef.current !== null;

	const shouldCreateAdsAccount =
		initHasExistingAdsAccountsRef.current === false &&
		initHasExistingMCAccountsRef.current === true;

	const shouldCreateMCAccount =
		initHasExistingAdsAccountsRef.current === true &&
		initHasExistingMCAccountsRef.current === false;

	const shouldCreateBothAccounts =
		! initHasExistingAdsAccountsRef.current &&
		! initHasExistingMCAccountsRef.current;

	const isCreatingAccounts =
		isCreatingAdsAccountRef.current ||
		isCreatingMCAccountRef.current ||
		isCreatingBothAccountsRef.current;

	useEffect( () => {
		// Ads account check
		if ( isCreatingAdsAccountRef.current === true && ! loading ) {
			isCreatingAdsAccountRef.current = false;
			accountsCreatedRef.current = true;
		}

		// MC account check
		if (
			isCreatingMCAccountRef.current === true &&
			response?.status === 200
		) {
			isCreatingMCAccountRef.current = false;
			accountsCreatedRef.current = true;
		}

		// both accounts check
		if (
			isCreatingBothAccountsRef.current === true &&
			response?.status === 200 &&
			! loading
		) {
			isCreatingBothAccountsRef.current = false;
			accountsCreatedRef.current = true;
		}
	}, [ response, loading ] );

	useEffect( () => {
		const handleCreation = async () => {
			// Bail out if we haven't resolved the existing accounts checks yet or there's a creation in progress or the accounts have been created.
			if (
				! accountCreationChecksResolved ||
				isCreatingAccounts ||
				accountsCreatedRef.current
			) {
				return;
			}

			if ( shouldCreateAdsAccount ) {
				isCreatingAdsAccountRef.current = true;
				await upsertAdsAccount();
				return;
			}

			if ( shouldCreateMCAccount ) {
				isCreatingMCAccountRef.current = true;
				await handleCreateAccount();
				return;
			}

			if ( shouldCreateBothAccounts ) {
				isCreatingBothAccountsRef.current = true;
				await handleCreateAccount();
				await upsertAdsAccount();
			}
		};

		handleCreation();
	}, [
		accountCreationChecksResolved,
		isCreatingAccounts,
		shouldCreateAdsAccount,
		shouldCreateMCAccount,
		shouldCreateBothAccounts,
		handleCreateAccount,
		upsertAdsAccount,
	] );

	return {
		accountCreationChecksResolved,
		isCreatingOnlyAdsAccount: isCreatingAdsAccountRef.current,
		isCreatingOnlyMCAccount: isCreatingMCAccountRef.current,
		isCreatingBothAccounts: isCreatingBothAccountsRef.current,
		isCreatingAccounts,
		accountsCreated: accountsCreatedRef.current,
	};
};

export default useAutoCreateAdsMCAccounts;
