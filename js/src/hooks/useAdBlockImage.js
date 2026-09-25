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
 * Injects a hidden bait element with ad-like class names and checks if it gets
 * hidden by cosmetic filtering (e.g. uBlock Origin). Resolves true if blocked.
 *
 * @return {Promise<boolean>} Resolves to true if an adblocker is detected, false otherwise.
 */
const detectViaDOM = () =>
	new Promise( ( resolve ) => {
		const bait = document.createElement( 'div' );
		bait.className = 'adsbox pub_300x250 pub_300x250m';
		bait.style.cssText =
			'width:1px;height:1px;position:absolute;left:-9999px';
		document.body.appendChild( bait );

		setTimeout( () => {
			const blocked =
				bait.offsetHeight === 0 ||
				window.getComputedStyle( bait ).display === 'none';
			document.body.removeChild( bait );
			resolve( blocked );
		}, 100 );
	} );

/**
 * Fetches a known Google Ads URL and resolves true if the request is blocked.
 * Uses no-cors + HEAD to avoid CORS errors on success.
 *
 * The hostname (tpc.googlesyndication.com) is what triggers blocklists —
 * the path is irrelevant, but kept ad-like for consistency.
 *
 * @return {Promise<boolean>} Resolves to true if the request is blocked, false otherwise.
 */
const detectViaFetch = () =>
	fetch(
		`https://tpc.googlesyndication.com/pimgad/pagead?timestamp=${ Date.now() }`,
		{
			method: 'HEAD',
			mode: 'no-cors',
			credentials: 'omit',
			redirect: 'manual',
			cache: 'no-store',
		}
	)
		.then( () => false )
		.catch( () => true );

/**
 * Hook that detects adblocker and returns a function to get display image URLs.
 *
 * This hook:
 * - Detects adblocker on mount using just-detect-adblock library
 * - Falls back to a DOM bait check to catch stubborn blockers
 *   (e.g. uBlock Origin, Privacy Badger)
 * - Falls back to a fetch check against a known ad URL as a last resort,
 *   only if the DOM check also indicates blocking (to reduce false positives
 *   from network failures)
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
 * const { getDisplayImageUrl, isDetected, isLoading } = useAdBlockImage();
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
					return;
				}

				// Secondary trap: DOM bait catches cosmetic filters (e.g. uBlock Origin)
				// that don't block network requests.
				const domBlocked = await detectViaDOM();

				if ( domBlocked ) {
					setIsDetected( true );
					return;
				}

				// Last resort: fetch check against a known ad URL.
				// Only reached if DOM check passed — avoids false positives from
				// network failures alone.
				const fetchBlocked = await detectViaFetch();

				if ( fetchBlocked ) {
					setIsDetected( true );
				}
			} catch {
				// If detectAnyAdblocker throws, assume blocked
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
				'https://tpc.googlesyndication.com/'
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
