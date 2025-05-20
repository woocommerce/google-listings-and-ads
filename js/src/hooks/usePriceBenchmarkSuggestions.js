/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getPriceBenchmarkSuggestions';

const usePriceBenchmarkSuggestions = ( productId ) => {
	return useSelect(
		( select ) => {
			const selector = select( STORE_KEY );

			return {
				data: selector[ selectorName ]( productId ),
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[ productId ]
				),
			};
		},
		[ productId ]
	);
};

export default usePriceBenchmarkSuggestions;
