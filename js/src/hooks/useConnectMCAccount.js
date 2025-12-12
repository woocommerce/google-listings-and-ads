/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '~/data';
import { ERROR_SLOTS } from '~/data/constants';

const useConnectMCAccount = ( value ) => {
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchMCAccounts, result ] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts`,
		method: 'POST',
		data: { id: value },
	} );
	const {
		invalidateResolution,
		receiveDetailedError,
		clearDetailedErrorBySlot,
	} = useAppDispatch();

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		try {
			clearDetailedErrorBySlot( ERROR_SLOTS.GOOGLE_MC_CONNECTION );
			await fetchMCAccounts( { parse: false } );
			invalidateResolution( 'getGoogleMCAccount', [] );
		} catch ( e ) {
			if ( e?.code === 'fetch_error' ) {
				createNotice(
					'error',
					__(
						'Unable to connect your Google Merchant Center account. Please check your connection and try again.',
						'google-listings-and-ads'
					)
				);
				return;
			}

			if (
				e?.code === 'API_ERROR' &&
				! [ 409, 403 ].includes( e.data?.statusCode )
			) {
				receiveDetailedError( ERROR_SLOTS.GOOGLE_MC_CONNECTION, {
					...e.data.error,
					title: __( 'Connection Failed', 'google-listings-and-ads' ),
				} );
			}
		}
	};

	return [ handleConnectClick, result ];
};

export default useConnectMCAccount;
