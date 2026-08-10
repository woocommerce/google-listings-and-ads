/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from './useApiFetchCallback';
import useDispatchCoreNotices from './useDispatchCoreNotices';

/**
 * A hook that verifies the merchant's selected Search Console property: it POSTs to the verify
 * endpoint and invalidates the account resolution so the store re-fetches the (now verified)
 * connection state, showing an error notice if the request fails.
 *
 * Shared by every card that offers a "verify" action — the main verification step and the
 * "action needed" re-verify card both go through this same request/invalidate/error sequence.
 *
 * @return {{ onClick: Function, loading: boolean }} Click handler to wire to the verify button, and whether a request is in flight.
 */
const useVerifySearchConsoleProperty = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();

	const [ fetchVerify, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/search-console/verify`,
		method: 'POST',
	} );

	const onClick = async () => {
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

	return { onClick, loading };
};

export default useVerifySearchConsoleProperty;
