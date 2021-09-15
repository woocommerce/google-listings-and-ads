/**
 * External dependencies
 */
import { useEffect, useCallback, useRef } from '@wordpress/element';

/**
 * Returns a function to check whether the caller component is mounted or unmounted.
 * Usually, it's used to avoid the warning - Can't perform a React state update on an unmounted component.
 *
 * @return {() => boolean} A function returns a boolean that indicates the caller component is mounted or unmounted.
 */
export default function useIsMounted() {
	const mountedRef = useRef( false );
	useEffect( () => {
		mountedRef.current = true;
		return () => {
			mountedRef.current = false;
		};
	}, [] );
	return useCallback( () => mountedRef.current, [] );
}
