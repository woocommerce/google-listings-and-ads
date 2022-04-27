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
import useIsEqualRefValue from '.~/hooks/useIsEqualRefValue';

const useAppSelectDispatch = ( selector, ...args ) => {
	const { invalidateResolution } = useAppDispatch();
	const argsRefValue = useIsEqualRefValue( args );

	const invalidateResolutionCallback = useCallback( () => {
		invalidateResolution( selector, argsRefValue );
	}, [ invalidateResolution, selector, argsRefValue ] );

	return useSelect(
		( select ) => {
			const { isResolving, hasFinishedResolution } = select( STORE_KEY );

			const data = select( STORE_KEY )[ selector ]( ...argsRefValue );

			return {
				isResolving: isResolving( selector, argsRefValue ),
				hasFinishedResolution: hasFinishedResolution(
					selector,
					argsRefValue
				),
				data,
				invalidateResolution: invalidateResolutionCallback,
			};
		},
		[ invalidateResolutionCallback, selector, argsRefValue ]
	);
};

export default useAppSelectDispatch;
