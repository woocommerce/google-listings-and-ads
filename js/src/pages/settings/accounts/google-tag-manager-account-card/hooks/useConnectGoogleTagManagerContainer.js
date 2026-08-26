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
	'Unable to select this Google Tag Manager container. Please try again.',
	'google-listings-and-ads'
);

/**
 * A hook that selects the merchant's picked Google Tag Manager container and refreshes connection state.
 *
 * @return {{ selectContainer: ( containerId: string ) => Promise<void>, loading: boolean }} Click handler to wire to the "Save" button, and whether a request is in flight.
 */
const useConnectGoogleTagManagerContainer = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();

	const [ fetchSelectContainer, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/tag-manager/container`,
		method: 'POST',
	} );

	const selectContainer = async ( containerId ) => {
		try {
			await fetchSelectContainer( {
				data: { container_id: containerId },
			} );
			invalidateResolution( 'getGoogleTagManagerAccount', [] );
		} catch ( error ) {
			createNotice( 'error', ERROR_MESSAGE );
		}
	};

	return { selectContainer, loading };
};

export default useConnectGoogleTagManagerContainer;
