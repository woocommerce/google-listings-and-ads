/**
 * External dependencies
 */
import { useRef, useCallback } from 'react';
import { useReducedMotion } from '@wordpress/compose';

/**
 * Hook to scroll container into view
 *
 * @return {{containerRef: Object, scrollIntoView: Function}} A ref to attach to the container, and a callback that scrolls it into view.
 */
const useScrollIntoView = () => {
	const containerRef = useRef();
	const isReducedMotion = useReducedMotion();

	const scrollIntoView = useCallback( () => {
		containerRef.current?.scrollIntoView( {
			behavior: isReducedMotion ? 'auto' : 'smooth',
			inline: 'nearest',
			block: 'nearest',
		} );
	}, [ isReducedMotion ] );

	return { containerRef, scrollIntoView };
};

export default useScrollIntoView;
