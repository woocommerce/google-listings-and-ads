/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '.~/data/constants';
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useApiFetchCallback from './useApiFetchCallback';

const useUpsertAdsAccount = () => {
	const { googleAdsAccount } = useGoogleAdsAccount();
	const [ adsAccountID, setAdsAccountID ] = useState( null );

	useEffect( () => {
		if ( googleAdsAccount && googleAdsAccount.id !== 0 ) {
			setAdsAccountID( googleAdsAccount.id );
		}
	}, [ googleAdsAccount ] );

	return useApiFetchCallback( {
		path: `${ API_NAMESPACE }/ads/accounts`,
		method: 'POST',
		data: {
			id: adsAccountID || undefined,
		},
	} );
};

export default useUpsertAdsAccount;
