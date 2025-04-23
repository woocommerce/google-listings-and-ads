/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getPriceBenchmarkSuggestions';

const usePriceBenchmarkSuggestions = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			suggestions: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default usePriceBenchmarkSuggestions;
