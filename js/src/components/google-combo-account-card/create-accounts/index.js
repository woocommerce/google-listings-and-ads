/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCreateMCAccount from '../../google-mc-account-card/useCreateMCAccount';
import useUpsertAdsAccount from '.~/hooks/useUpsertAdsAccount';
import { receiveMCAccount } from '.~/data/actions';

/**
 * Create MC and Ads accounts.
 */
const CreateAccounts = ( { setIsCreatingAccounts } ) => {
	const [ handleCreateAccount, { data: account, response } ] =
		useCreateMCAccount();

	const [ upsertAdsAccount ] = useUpsertAdsAccount();

	if ( response?.status === 200 ) {
		receiveMCAccount( account );
		setIsCreatingAccounts( false );
	}

	useEffect( () => {
		setIsCreatingAccounts( true );

		const createAccounts = async () => {
			await handleCreateAccount();
			await upsertAdsAccount();
		};

		createAccounts();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	return null;
};

export default CreateAccounts;
