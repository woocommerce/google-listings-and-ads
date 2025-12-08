/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { ERROR_SLOTS } from '~/data/constants';

const useCreateMCAccount = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution, receiveDetailedError } = useAppDispatch();
	const [ fetchCreateMCAccount, result ] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts`,
		method: 'POST',
	} );

	const handleCreateAccount = async () => {
		try {
			await fetchCreateMCAccount( {
				data: result.error?.id && { id: result.error.id },
				parse: false,
			} );
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
			if ( e?.code === 'API_ERROR' ) {
				receiveDetailedError( ERROR_SLOTS.GOOGLE_MC_CONNECTION, {
					...e,
					title: __( 'Connection Failed', 'google-listings-and-ads' ),
				} );
			} else {
				const message = __(
					'Unable to connect Merchant Center account. Please try again later.',
					'google-listings-and-ads'
				);
				createNotice( 'error', message );
			}
		}
	};

	return [ handleCreateAccount, result ];
};

export default useCreateMCAccount;
