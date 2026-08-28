/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

/**
 * A hook that connects the merchant's picked Google Tag Manager account and refreshes connection
 * state.
 *
 * @return {{ connect: ( accountId: string ) => Promise<void>, loading: boolean }} `connect` — click handler to wire to the "Connect" button — and whether a request is in flight.
 */
const useConnectGoogleTagManagerAccount = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { fetchGoogleTagManagerAccount } = useAppDispatch();

	const [ fetchConnect, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/tag-manager/accounts`,
		method: 'POST',
	} );

	/**
	 * @param {string} accountId The picked account's ID.
	 */
	const connect = async ( accountId ) => {
		try {
			// await fetchConnect( { data: { id: accountId } } );
			await fetchConnect( accountId );
			await fetchGoogleTagManagerAccount();
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to connect this Google Tag Manager account. Please try again.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return { connect, loading };
};

export default useConnectGoogleTagManagerAccount;
