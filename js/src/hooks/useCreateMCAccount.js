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
import extractDetailedApiError from '~/utils/extractDetailedApiError';
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

			const detailedError = await extractDetailedApiError( e );

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

	return [ handleCreateAccount, result ];
};

export default useCreateMCAccount;
