/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import { API_NAMESPACE } from '.~/data/constants';
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useApiFetchCallback from './useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';

/**
 * Set up a Google Ads account.
 *
 * @return {[Function, Object]} Returns a function to create or update a Google Ads account and the data returned by the API request.
 */
const useUpsertAdsAccount = () => {
	// Check if there is a connected Google Ads account which in this case will update the account.
	// If not, it means we are creating a new account.
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { createNotice } = useDispatchCoreNotices();
	const { fetchGoogleAdsAccount, fetchGoogleAdsAccountStatus } =
		useAppDispatch();

	const [ fetchCreateAccount, data ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/ads/accounts`,
		method: 'POST',
		data: {
			id: googleAdsAccount?.id || undefined,
		},
	} );

	const upsertAdsAccount = async () => {
		try {
			await fetchCreateAccount();
		} catch ( e ) {
			// For status code 428, we want to allow users to continue and proceed,
			// so we swallow the error for status code 428,
			// and only display error message and exit this function for non-428 error.
			if ( e.status !== 428 ) {
				createNotice(
					'error',
					__(
						'Unable to create Google Ads account. Please try again later.',
						'google-listings-and-ads'
					)
				);
			}
			return;
		}

		// Update Google Ads data in the data store after posting an account update.
		await fetchGoogleAdsAccount();
		await fetchGoogleAdsAccountStatus();
	};

	return [ upsertAdsAccount, data ];
};

export default useUpsertAdsAccount;
