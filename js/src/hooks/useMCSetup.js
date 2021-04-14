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

/**
 * Get Merchant Center setup info.
 */
const useMCSetup = () => {
	const { invalidateResolution } = useAppDispatch();

	const invalidateResolutionCallback = useCallback( () => {
		invalidateResolution( 'getMCSetup', [] );
	}, [ invalidateResolution ] );

	return useSelect( ( select ) => {
		const {
			getMCSetup,
			isResolving,
			hasStartedResolution,
			hasFinishedResolution,
		} = select( STORE_KEY );

		const data = getMCSetup();

		return {
			isResolving: isResolving( 'getMCSetup' ),
			hasStartedResolution: hasStartedResolution( 'getMCSetup' ),
			hasFinishedResolution: hasFinishedResolution( 'getMCSetup' ),
			data,
			invalidateResolution: invalidateResolutionCallback,
		};
	} );
};

export default useMCSetup;
