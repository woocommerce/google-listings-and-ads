/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';

const useCreateMCAccount = () => {
	const singletonRef = useRef( null );
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();
	const [ fetchCreateMCAccount, result ] = useApiFetchCallback( {
		path: `/wc/gla/mc/accounts`,
		method: 'POST',
	} );

	if ( singletonRef.current ) {
		return singletonRef.current;
	}

	const handleCreateAccount = async () => {
		try {
			await fetchCreateMCAccount( {
				data: result.error?.id && { id: result.error.id },
				parse: false,
			} );
			invalidateResolution( 'getGoogleMCAccount', [] );
		} catch ( e ) {
			if ( ! [ 403, 503 ].includes( e.status ) ) {
				const body = await e.json();
				const message =
					body.message ||
					__(
						'Unable to create Merchant Center account. Please try again later.',
						'google-listings-and-ads'
					);
				createNotice( 'error', message );
			}
		}
	};

	singletonRef.current = [ handleCreateAccount, result ];
	return singletonRef.current;
};

export default useCreateMCAccount;
