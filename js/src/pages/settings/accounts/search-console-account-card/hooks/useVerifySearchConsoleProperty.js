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
 * A hook that verifies the merchant's Search Console property and refreshes connection state.
 *
 * @return {{ verify: Function, loading: boolean }} Click handler to wire to the verify button, and whether a request is in flight.
 */
const useVerifySearchConsoleProperty = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();

	const [ fetchVerify, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/search-console/verify`,
		method: 'POST',
	} );

	const verify = async () => {
		try {
			await fetchVerify();
			invalidateResolution( 'getSearchConsoleAccount', [] );
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to verify your Search Console property. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return { verify, loading };
};

export default useVerifySearchConsoleProperty;
