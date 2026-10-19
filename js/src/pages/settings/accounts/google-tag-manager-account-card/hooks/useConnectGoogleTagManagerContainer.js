/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import { handleApiError } from '~/utils/handleError';
import useRefreshGoogleTagManagerConnection from './useRefreshGoogleTagManagerConnection';

/**
 * A hook returning a function that connects an existing Google Tag Manager container, then
 * refreshes the connection so the card switches to its connected state. A failed request is
 * reported to the merchant.
 *
 * @return {Array} `[ connectContainer, { loading } ]`: the function that connects the container with
 * the given ID; and `loading`, true while the request or the refresh is in progress.
 */
export default function useConnectGoogleTagManagerContainer() {
	const { refreshConnection, loading: isRefreshing } =
		useRefreshGoogleTagManagerConnection();
	const [ fetchConnectContainer, { loading: isConnecting } ] =
		useApiFetchCallback( {
			path: `${ API_NAMESPACE }/tag-manager/containers`,
			method: 'POST',
		} );

	const connectContainer = useCallback(
		async ( id ) => {
			try {
				await fetchConnectContainer( { data: { id } } );
				await refreshConnection();
			} catch ( error ) {
				handleApiError(
					error,
					__(
						'There was an error connecting your Google Tag Manager container.',
						'google-listings-and-ads'
					)
				);
			}
		},
		[ fetchConnectContainer, refreshConnection ]
	);

	return [ connectContainer, { loading: isConnecting || isRefreshing } ];
}
