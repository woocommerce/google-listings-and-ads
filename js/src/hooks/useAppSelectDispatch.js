/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import { useAppDispatch } from '.~/data';

const useAppSelectDispatch = ( selector, ...args ) => {
	const { invalidateResolution } = useAppDispatch();

	const invalidateResolutionCallback = useCallback( () => {
		invalidateResolution( selector, [ ...args ] );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ invalidateResolution, selector, ...args ] );

	return useSelect(
		( select ) => {
			const { hasFinishedResolution } = select( STORE_KEY );

			const data = select( STORE_KEY )[ selector ]( ...args );

			return {
				hasFinishedResolution: hasFinishedResolution( selector, [
					...args,
				] ),
				data,
				invalidateResolution: invalidateResolutionCallback,
			};
		},
		[ invalidateResolutionCallback, selector, ...args ]
	);
};

export default useAppSelectDispatch;
