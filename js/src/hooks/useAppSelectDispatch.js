/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useCallback, useRef } from '@wordpress/element';
import isEqual from 'lodash/isEqual';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import { useAppDispatch } from '.~/data';

const useAppSelectDispatch = ( selector, ...args ) => {
	const { invalidateResolution } = useAppDispatch();

	const argsRef = useRef( args );
	if ( ! isEqual( argsRef.current, args ) ) {
		argsRef.current = args;
	}

	const invalidateResolutionCallback = useCallback( () => {
		invalidateResolution( selector, argsRef.current );
	}, [ invalidateResolution, selector ] );

	return useSelect(
		( select ) => {
			const { hasFinishedResolution } = select( STORE_KEY );

			const data = select( STORE_KEY )[ selector ]( ...argsRef.current );

			return {
				hasFinishedResolution: hasFinishedResolution(
					selector,
					argsRef.current
				),
				data,
				invalidateResolution: invalidateResolutionCallback,
			};
		},
		[ invalidateResolutionCallback, selector ]
	);
};

export default useAppSelectDispatch;
