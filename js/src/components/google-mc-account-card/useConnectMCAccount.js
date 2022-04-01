/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import { useAppDispatch } from '.~/data';

const useConnectMCAccount = ( value ) => {
	const { createNotice } = useDispatchCoreNotices();
	const [ fetchMCAccounts, result ] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts`,
		method: 'POST',
		data: { id: value },
	} );
	const { invalidateResolution } = useAppDispatch();

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		try {
			await fetchMCAccounts( { parse: false } );
			invalidateResolution( 'getGoogleMCAccount', [] );
		} catch ( e ) {
			if ( ! [ 409, 403 ].includes( e.status ) ) {
				const body = await e.json();
				const message =
					body.message ||
					__(
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
