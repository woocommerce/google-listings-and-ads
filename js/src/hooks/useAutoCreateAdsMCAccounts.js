/**
 * External dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';

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
} from '.~/constants';

/**
 * Custom hook to handle the creation of Google Merchant Center (MC) and Google Ads accounts.
 *
 * @typedef {Object} AutoCreateAccountsStatus
 * @property {boolean} accountCreationChecksResolved Whether the checks for account creation have been resolved.
 * @property {boolean} isCreatingAccounts Whether the accounts are being created.
 * @property {string|null} isCreatingWhichAccount The type of account that is being created.
 *
 * @return {AutoCreateAccountsStatus} Object containing properties related to the account creation status.
 */
const useAutoCreateAdsMCAccounts = () => {
	const [ accountsState, setAccountsState ] = useState( {
		accountsCreated: false,
		isCreatingWhichAccount: null,
	} );

	const { accountsCreated, isCreatingWhichAccount } = accountsState;
	const initHasExistingMCAccountsRef = useRef( null );
	const initHasExistingAdsAccountsRef = useRef( null );

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

	const { hasFinishedResolution: hasFinishedResolutionForGoogleMCAccount } =
		useGoogleMCAccount();

	const [ handleCreateAccount, { response } ] = useCreateMCAccount();
	const [ upsertAdsAccount, { loading } ] = useUpsertAdsAccount();

	const hasExistingMCAccount = existingMCAccounts?.length > 0;
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

	const isCreatingAccounts = !! isCreatingWhichAccount;

	useEffect( () => {
		if ( ! response && loading ) {
			return;
		}

		if ( isCreatingWhichAccount === CREATING_ADS_ACCOUNT && ! loading ) {
			setAccountsState( ( prevState ) => ( {
				...prevState,
				isCreatingWhichAccount: null,
				accountsCreated: true,
			} ) );
			return;
		}

		const mcAccountCreated = [ 200, 403, 406, 503 ].includes(
			response?.status
		);

		if (
			isCreatingWhichAccount === CREATING_MC_ACCOUNT &&
			mcAccountCreated
		) {
			setAccountsState( ( prevState ) => ( {
				...prevState,
				isCreatingWhichAccount: null,
				accountsCreated: true,
			} ) );
			return;
		}

		if (
			isCreatingWhichAccount === CREATING_BOTH_ACCOUNTS &&
			mcAccountCreated &&
			! loading
		) {
			setAccountsState( ( prevState ) => ( {
				...prevState,
				isCreatingWhichAccount: null,
				accountsCreated: true,
			} ) );
		}
	}, [ response, loading, isCreatingWhichAccount ] );

	useEffect( () => {
		const handleCreation = async () => {
			if (
				! accountCreationChecksResolved ||
				isCreatingAccounts ||
				accountsCreated
			) {
				return;
			}

			if ( shouldCreateAdsAccount ) {
				setAccountsState( ( prevState ) => ( {
					...prevState,
					isCreatingWhichAccount: CREATING_ADS_ACCOUNT,
				} ) );
				await upsertAdsAccount();
				return;
			}

			if ( shouldCreateMCAccount ) {
				setAccountsState( ( prevState ) => ( {
					...prevState,
					isCreatingWhichAccount: CREATING_MC_ACCOUNT,
				} ) );
				await handleCreateAccount();
				return;
			}

			if ( shouldCreateBothAccounts ) {
				setAccountsState( ( prevState ) => ( {
					...prevState,
					isCreatingWhichAccount: CREATING_BOTH_ACCOUNTS,
				} ) );
				await handleCreateAccount();
				await upsertAdsAccount();
			}
		};

		handleCreation();
	}, [
		accountCreationChecksResolved,
		accountsCreated,
		handleCreateAccount,
		isCreatingAccounts,
		shouldCreateAdsAccount,
		shouldCreateBothAccounts,
		shouldCreateMCAccount,
		upsertAdsAccount,
	] );

	return {
		accountCreationChecksResolved,
		isCreatingAccounts,
		isCreatingWhichAccount,
	};
};

export default useAutoCreateAdsMCAccounts;
