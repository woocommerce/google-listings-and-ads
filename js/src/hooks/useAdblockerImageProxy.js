/**
 * External dependencies
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { detectAnyAdblocker } from 'just-detect-adblock';

/**
 * Internal dependencies
 */
import getProxiedImageUrl from '~/utils/getProxiedImageUrl';

/**
 * Hook that detects adblocker and returns a function to get proxied image URLs.
 *
 * This hook:
 * - Detects adblocker on mount using just-detect-adblock library
 * - Returns a function that conditionally proxies URLs based on detection
 * - Returns detection status (isDetected, isLoading)
 *
 * The returned function only proxies Google Ads images (tpc.googlesyndication.com)
 * when an adblocker is detected. Otherwise, it returns the original URL to avoid
 * unnecessary proxying.
 *
 * @return {Array} Array containing [getProxyUrl function, detection state object].
 *
 * @example
 * const [ getProxyUrl, { isDetected, isLoading } ] = useAdblockerImageProxy();
 *
 * // Use in render
 * <img src={ getProxyUrl( imageUrl ) } />
 */
const useAdblockerImageProxy = () => {
	const [ isDetected, setIsDetected ] = useState( false );
	const [ isLoading, setIsLoading ] = useState( true );
	const hasRunRef = useRef( false );

	useEffect( () => {
		if ( hasRunRef.current ) {
			return;
		}
		hasRunRef.current = true;

		const detectAdblock = async () => {
			try {
				const detected = await detectAnyAdblocker();

				if ( detected ) {
					setIsDetected( true );
					return;
				}

				// Probe a URL that always exists in WordPress with an ad-related query
				// string to trigger filter rules. /wp-json/ is the REST API root and
				// returns 200 on all WP 4.7+ sites. If fetch throws, the request was blocked.
				const params = [
					'gla-adblocker=/adsense/pagead2.googlesyndication.com/pagead/js/adsbygoogle.js',
					`timestamp=${ Date.now() }`,
				];
				await fetch( `/wp-json/?${ params.join( '&' ) }`, {
					credentials: 'omit',
					redirect: 'manual',
				} );
				setIsDetected( false );
			} catch {
				setIsDetected( true );
			} finally {
				setIsLoading( false );
			}
		};

		detectAdblock();
	}, [] );

	const getProxyUrl = useCallback(
		( imageUrl ) => {
			if (
				! imageUrl ||
				! isDetected ||
				! imageUrl.includes( 'tpc.googlesyndication.com' )
			) {
				return imageUrl;
			}

			return getProxiedImageUrl( imageUrl );
		},
		[ isDetected ]
	);

	return [ getProxyUrl, { isDetected, isLoading } ];
};

export default useAdblockerImageProxy;
