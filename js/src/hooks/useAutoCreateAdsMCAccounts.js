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
	const initHasExistingMCAAccountsRef = useRef( null );
	const initHasExistingAdsAccountsRef = useRef( null );
	const accountsCreatedRef = useRef( false );

	const {
		data: existingMCAccounts,
		hasFinishedResolution: hasFinishedResolutionForExistingMCAccounts,
	} = useExistingGoogleMCAccounts();

	const {
		existingAccounts: existingAdsAccount,
		isResolving: isResolvingExistingAdsAccount,
	} = useExistingGoogleAdsAccounts();

	const [ handleCreateAccount, { response } ] = useCreateMCAccount();
	const [ upsertAdsAccount, { loading } ] = useUpsertAdsAccount();

	const hasExistingMCAccount = existingMCAccounts?.length > 0;
	const hasExistingAdsAccount = existingAdsAccount?.length > 0;

	if (
		initHasExistingMCAAccountsRef.current === null &&
		hasFinishedResolutionForExistingMCAccounts
	) {
		initHasExistingMCAAccountsRef.current = hasExistingMCAccount;
	}

	if (
		initHasExistingAdsAccountsRef.current === null &&
		! isResolvingExistingAdsAccount
	) {
		initHasExistingAdsAccountsRef.current = hasExistingAdsAccount;
	}

	const createMCAAccount = useCallback( async () => {
		await handleCreateAccount();
	}, [ handleCreateAccount ] );

	const createAdsAccount = useCallback( async () => {
		await upsertAdsAccount();
	}, [ upsertAdsAccount ] );

	const createBothAccounts = useCallback( async () => {
		await createMCAAccount();
		await createAdsAccount();
	}, [ createMCAAccount, createAdsAccount ] );

	const accountCreationChecksResolved =
		initHasExistingAdsAccountsRef.current !== null &&
		initHasExistingMCAAccountsRef.current !== null;

	const shouldCreateAdsAccount =
		initHasExistingAdsAccountsRef.current === false &&
		initHasExistingMCAAccountsRef.current === true;

	const shouldCreateMCAccount =
		initHasExistingAdsAccountsRef.current === true &&
		initHasExistingMCAAccountsRef.current === false;

	const shouldCreateBothAccounts =
		! initHasExistingAdsAccountsRef.current &&
		! initHasExistingMCAAccountsRef.current;

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
				await createMCAAccount;
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
		createMCAAccount,
		isCreatingAccounts,
		shouldCreateAdsAccount,
		shouldCreateMCAccount,
		shouldCreateBothAccounts,
	] );

	return {
		accountCreationChecksResolved,
		isCreatingAdsAccount: isCreatingAdsAccountsRef.current,
		isCreatingMCAccount: isCreatingMCAccountsRef.current,
		isCreatingBothAccounts: isCreatingBothAccountsRef.current,
		isCreatingAccounts,
	};
};

export default useAutoCreateAdsMCAccounts;
