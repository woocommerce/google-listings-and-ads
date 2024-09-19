/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCreateMCAccount from '../../google-mc-account-card/useCreateMCAccount';
import useUpsertAdsAccount from '../../../hooks/useUpsertAdsAccount';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';

/**
 * Create MC and Ads accounts.
 */
const CreateAccounts = ( { setAccounts } ) => {
	const { googleAdsAccount } = useGoogleAdsAccount();

	const [ handleCreateAccount, { loading, response } ] = useCreateMCAccount();
	const [ , { loading: adsAccountsLoading } ] = useUpsertAdsAccount();

	useEffect( () => {
		console.log( 'googleAdsAccount', googleAdsAccount );
		console.log( 'response', response );

		if (
			! loading &&
			! adsAccountsLoading &&
			response &&
			response.status === 200
		) {
			const createMCAccount = async () => {
				await handleCreateAccount();
			};

			createMCAccount();

			setAccounts( {
				MCAccounts: [ response.data ],
				AdsAccounts: [ googleAdsAccount.id ],
			} );
		}
	}, [
		loading,
		adsAccountsLoading,
		response,
		handleCreateAccount,
		googleAdsAccount,
		setAccounts,
	] );

	return null;
};

export default CreateAccounts;
