/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '.~/data/constants';
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useApiFetchCallback from './useApiFetchCallback';

const useUpsertAdsAccount = () => {
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
