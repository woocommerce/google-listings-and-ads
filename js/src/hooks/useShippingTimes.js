/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data';

const useShippingTimes = () => {
	return useSelect( ( select ) => {
		const { getShippingTimes, isResolving } = select( STORE_KEY );

		const data = getShippingTimes();
		const loading = isResolving( 'getShippingTimes' );

		return {
			loading,
			data,
		};
	} );
};

export default useShippingTimes;
