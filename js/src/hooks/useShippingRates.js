/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data';

const useShippingRates = () => {
	return useSelect( ( select ) => {
		const { getShippingRates, isResolving } = select( STORE_KEY );

		const data = getShippingRates();
		const loading = isResolving( 'getShippingRates' );

		return {
			loading,
			data,
		};
	} );
};

export default useShippingRates;
