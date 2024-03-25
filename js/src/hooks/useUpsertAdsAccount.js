/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '.~/data/constants';
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useApiFetchCallback from './useApiFetchCallback';

const useUpsertAdsAccount = () => {
	// Check if there is a connected Google Ads account which in this case will update the account.
	// If not, it means we are creating a new account.
	const { googleAdsAccount } = useGoogleAdsAccount();

	return useApiFetchCallback( {
		path: `${ API_NAMESPACE }/ads/accounts`,
		method: 'POST',
		data: {
			id: googleAdsAccount?.id || undefined,
		},
	} );
};

export default useUpsertAdsAccount;
