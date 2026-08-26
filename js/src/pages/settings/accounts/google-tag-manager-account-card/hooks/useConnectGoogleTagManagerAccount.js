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

const ERROR_MESSAGE = __(
	'Unable to connect this Google Tag Manager account. Please try again.',
	'google-listings-and-ads'
);

/**
 * A hook that connects the merchant's picked Google Tag Manager account and refreshes connection state.
 *
 * @return {{ connect: ( accountId: string ) => Promise<void>, loading: boolean }} Click handler to wire to the "Connect" button, and whether a request is in flight.
 */
const useConnectGoogleTagManagerAccount = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();

	const [ fetchConnect, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/tag-manager/account`,
		method: 'POST',
	} );

	const connect = async ( accountId ) => {
		try {
			await fetchConnect( { data: { account_id: accountId } } );
			invalidateResolution( 'getGoogleTagManagerAccount', [] );
		} catch ( error ) {
			createNotice( 'error', ERROR_MESSAGE );
		}
	};

	return { connect, loading };
};

export default useConnectGoogleTagManagerAccount;
