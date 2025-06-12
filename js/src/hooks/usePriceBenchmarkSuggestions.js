/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getPriceBenchmarkSuggestions';

const usePriceBenchmarkSuggestions = ( args ) => {
	return useSelect(
		( select ) => {
			const selector = select( STORE_KEY );
			const { product_id: productId } = args;

			if ( productId ) {
				const item = selector.getPriceBenchmarkSuggestion( productId );

				if ( item ) {
					return {
						data: item,
						hasFinishedResolution: true,
					};
				}

				return {
					data: null,
					hasFinishedResolution: true,
				};
			}

			return {
				data: selector[ selectorName ]( args ),
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[ args ]
				),
			};
		},
		[ args ]
	);
};

export default usePriceBenchmarkSuggestions;
