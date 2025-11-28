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

const useConnectMCAccount = ( value ) => {
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchMCAccounts, result ] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts`,
		method: 'POST',
		data: { id: value },
	} );
	const { invalidateResolution, receiveDetailedError } = useAppDispatch();

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		try {
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

			if ( e?.status >= 400 && e?.status < 600 ) {
				const body = await e.json();
				receiveDetailedError( 'CONNECT_MC_ACCOUNT', body );
			} else {
				const message = __(
					'Unable to connect Merchant Center account. Please try again later.',
					'google-listings-and-ads'
				);
				createNotice( 'error', message );
			}
		}
	};

	return [ handleConnectClick, result ];
};

export default useConnectMCAccount;
