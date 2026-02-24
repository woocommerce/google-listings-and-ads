/**
 * External dependencies
 */
import { detectAnyAdblocker } from 'just-detect-adblock';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import getProxiedImageUrl from '~/utils/getProxiedImageUrl';

/**
 * Hook that detects adblocker and returns a function to get display image URLs.
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
 * @return {Object} { getDisplayImageUrl: Function, isDetected: boolean, isLoading: boolean }
 *
 * @example
 * const { getDisplayImageUrl, isDetected, isLoading } = useAdBlockerImageProxy();
 *
 * // Use in render
 * <img src={ getDisplayImageUrl( imageUrl ) } />
 */
const useAdBlockImage = () => {
	const [ isDetected, setIsDetected ] = useState( false );
	const [ isLoading, setIsLoading ] = useState( true );
	const hasDetectedRef = useRef( false );

	useEffect( () => {
		if ( hasDetectedRef.current ) {
			return;
		}
		hasDetectedRef.current = true;

		const detectAdblock = async () => {
			try {
				const detected = await detectAnyAdblocker();

				if ( detected ) {
					setIsDetected( true );
				} else {
					// Secondary trap for stubborn blockers
					const params = new URLSearchParams( {
						'gla-ad-blocker':
							'tpc.googlesyndication.com/pimgad/11111',
						timestamp: Date.now(),
					} );

					await fetch( `/favicon.ico?${ params.toString() }`, {
						credentials: 'omit',
						redirect: 'manual',
					} );
				}
			} catch {
				// If fetch fails or detectAnyAdblocker throws, assume blocked
				setIsDetected( true );
			} finally {
				setIsLoading( false );
			}
		};

		detectAdblock();
	}, [] );

	const getDisplayImageUrl = useCallback(
		( imageUrl ) => {
			const isGoogleAd = imageUrl?.startsWith(
				'https://tpc.googlesyndication.com/pimgad'
			);

			if ( ! imageUrl || ! isDetected || ! isGoogleAd ) {
				return imageUrl;
			}

			return getProxiedImageUrl( imageUrl );
		},
		[ isDetected ]
	);

	return { getDisplayImageUrl, isDetected, isLoading };
};

export default useAdBlockImage;
