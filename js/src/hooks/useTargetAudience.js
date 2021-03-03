/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data';

const useTargetAudience = () => {
	return useSelect( ( select ) => {
		const { getTargetAudience, isResolving } = select( STORE_KEY );

		const data = getTargetAudience();
		const loading = isResolving( 'getTargetAudience' );

		return {
			loading,
			data,
		};
	} );
};

export default useTargetAudience;
