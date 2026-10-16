/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import extractDetailedApiError from '~/utils/extractDetailedApiError';
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
		clearDetailedErrorBySlots,
	} = useAppDispatch();

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		try {
			clearDetailedErrorBySlots( [
				ERROR_SLOTS.GOOGLE_MC_CONNECTION_ERROR_SLOT,
			] );
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

			const detailedError = await extractDetailedApiError( e, {
				ignoredStatusCodes: [ 403, 409 ],
			} );

			if ( detailedError ) {
				receiveDetailedError(
					ERROR_SLOTS.GOOGLE_MC_CONNECTION_ERROR_SLOT,
					{
						...detailedError.data,
						title: __(
							'Connection Failed',
							'google-listings-and-ads'
						),
					}
				);
			}
		}
	};

	return [ handleConnectClick, result ];
};

export default useConnectMCAccount;
