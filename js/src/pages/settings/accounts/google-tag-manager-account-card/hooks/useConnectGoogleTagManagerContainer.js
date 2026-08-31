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
 * A hook that selects the merchant's picked Google Tag Manager container and refreshes connection
 * state.
 *
 * @return {{ selectContainer: ( containerId: string ) => Promise<void>, loading: boolean }} `selectContainer` — click handler to wire to the "Save" button — and whether a request is in flight.
 */
const useConnectGoogleTagManagerContainer = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { fetchGoogleTagManagerAccount } = useAppDispatch();

	const [ fetchSelectContainer, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/tag-manager/containers`,
		method: 'POST',
	} );

	/**
	 * @param {string} containerId The picked container's ID.
	 */
	const selectContainer = async ( containerId ) => {
		try {
			await fetchSelectContainer( containerId );
			await fetchGoogleTagManagerAccount();
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to select this Google Tag Manager container. Please try again.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return { selectContainer, loading };
};

export default useConnectGoogleTagManagerContainer;
