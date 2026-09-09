/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import useApiFetchCallback from './useApiFetchCallback';
import useDispatchCoreNotices from './useDispatchCoreNotices';

const PROPERTIES_PATH = `${ API_NAMESPACE }/search-console/properties`;

/**
 * Select an existing Google Search Console property, or create a new one — the single POST
 * `search-console/properties` primitive shared by the manual multi-match picker
 * ({@see PropertySelection}) and the automatic 0/1-match resolution
 * ({@see useAutoResolveSearchConsoleProperty}).
 *
 * @return {Array} [ resolveProperty, { loading } ]
 * 		- `resolveProperty( siteUrl )` selects the given property, or creates a new one when
 * 		  `siteUrl` is omitted.
 * 		- `loading` whether a request is currently in flight.
 */
const useResolveSearchConsoleProperty = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();

	const [ fetchResolveProperty, { loading } ] = useApiFetchCallback( {
		path: PROPERTIES_PATH,
		method: 'POST',
	} );

	const resolveProperty = useCallback(
		async ( siteUrl ) => {
			try {
				await fetchResolveProperty( {
					data: siteUrl ? { site_url: siteUrl } : {},
				} );
				invalidateResolution( 'getGoogleSearchConsoleAccount', [] );
			} catch ( error ) {
				// Nothing changed server-side on failure (e.g. the chosen match is no longer
				// usable) — refresh to get a fresh property list and show the selector again.
				invalidateResolution( 'getGoogleSearchConsoleAccount', [] );
				invalidateResolution( 'getGoogleSearchConsoleProperties', [] );
				createNotice(
					'error',
					__(
						'The selected property is no longer available. Please try again.',
						'google-listings-and-ads'
					)
				);
				throw error;
			}
		},
		[ fetchResolveProperty, invalidateResolution, createNotice ]
	);

	return [ resolveProperty, { loading } ];
};

export default useResolveSearchConsoleProperty;
