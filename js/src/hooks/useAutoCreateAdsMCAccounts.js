/**
 * External dependencies
 */
import { useEffect, useRef, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCreateMCAccount from '../components/google-mc-account-card/useCreateMCAccount';
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
 * @property {boolean} isCreatingAdsAccount Indicates if the Google Ads account is currently being created.
 * @property {boolean} isCreatingMCAccount Indicates if the Google Merchant Center account is currently being created.
 * @property {boolean} accountCreationChecksResolved Indicates if the account creation checks (for existing accounts) have been resolved.
 * @property {boolean} accountsCreated Indicates if both the Google Ads and Google Merchant Center accounts have been successfully created.
 *
 * @return {AutoCreateAccountsStatus} Object containing properties related to the account creation status.
 */
const useAutoCreateAdsMCAccounts = () => {
	/**
	 * Refs are used to avoid the re-render of the parent component.
	 *
	 * accountCreationChecksResolvedRef - Indicates if the account creation checks have been resolved.
	 * isCreatingAccountsRef - Indicates if the accounts are being created.
	 * accountsCreatedRef - Indicates if the accounts have been created.
	 */
	const isCreatingBothAccountsRef = useRef( false );
	const isCreatingAdsAccountsRef = useRef( false );
	const isCreatingMCAccountsRef = useRef( false );
	const initHasExistingMCAccountsRef = useRef( null );
	const initHasExistingAdsAccountsRef = useRef( null );
	const accountsCreatedRef = useRef( false );

	const {
		data: existingMCAccounts,
		hasFinishedResolution: hasFinishedResolutionForExistingMCAccounts,
	} = useExistingGoogleMCAccounts();

	const {
		existingAccounts: existingAdsAccount,
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
		hasGoogleAdsConnection || existingAdsAccount?.length > 0;

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

	const createMCAccount = useCallback( async () => {
		await handleCreateAccount();
	}, [ handleCreateAccount ] );

	const createAdsAccount = useCallback( async () => {
		await upsertAdsAccount();
	}, [ upsertAdsAccount ] );

	const createBothAccounts = useCallback( async () => {
		await createMCAccount();
		await createAdsAccount();
	}, [ createMCAccount, createAdsAccount ] );

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
		isCreatingAdsAccountsRef.current ||
		isCreatingMCAccountsRef.current ||
		isCreatingBothAccountsRef.current;

	useEffect( () => {
		// Ads account check
		if ( isCreatingAdsAccountsRef.current === true && ! loading ) {
			isCreatingAdsAccountsRef.current = false;
			accountsCreatedRef.current = true;
		}

		// MC account check
		if (
			isCreatingMCAccountsRef.current === true &&
			response?.status === 200
		) {
			isCreatingMCAccountsRef.current = false;
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
				isCreatingAdsAccountsRef.current = true;
				await createAdsAccount();
				return;
			}

			if ( shouldCreateMCAccount ) {
				isCreatingMCAccountsRef.current = true;
				await createMCAccount();
				return;
			}

			if ( shouldCreateBothAccounts ) {
				isCreatingBothAccountsRef.current = true;
				await createBothAccounts();
			}
		};

		handleCreation();
	}, [
		accountCreationChecksResolved,
		createBothAccounts,
		createAdsAccount,
		createMCAccount,
		isCreatingAccounts,
		shouldCreateAdsAccount,
		shouldCreateMCAccount,
		shouldCreateBothAccounts,
	] );

	return {
		accountCreationChecksResolved,
		hasExistingMCAccounts: initHasExistingMCAccountsRef.current,
		isCreatingAdsAccount: isCreatingAdsAccountsRef.current,
		isCreatingMCAccount: isCreatingMCAccountsRef.current,
		isCreatingBothAccounts: isCreatingBothAccountsRef.current,
		isCreatingAccounts,
		accountsCreated: accountsCreatedRef.current,
	};
};

export default useAutoCreateAdsMCAccounts;
