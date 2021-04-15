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

const selectorName = 'getMCSetup';

/**
 * Get Merchant Center setup info.
 */
const useMCSetup = () => {
	const { invalidateResolution } = useAppDispatch();

	const invalidateResolutionCallback = useCallback( () => {
		invalidateResolution( selectorName, [] );
	}, [ invalidateResolution ] );

	return useSelect( ( select ) => {
		const { hasFinishedResolution } = select( STORE_KEY );

		const data = select( STORE_KEY )[ selectorName ]();

		return {
			hasFinishedResolution: hasFinishedResolution( selectorName ),
			data,
			invalidateResolution: invalidateResolutionCallback,
		};
	} );
};

export default useMCSetup;
