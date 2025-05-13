/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getPriceBenchmarkProductMetrics';

const usePriceBenchmarkProductMetrics = ( productId ) => {
	return useSelect(
		( select ) => {
			const selector = select( STORE_KEY );

			return {
				product: selector[ selectorName ]( productId ),
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[ productId ]
				),
			};
		},
		[ productId ]
	);
};

export default usePriceBenchmarkProductMetrics;
