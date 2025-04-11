/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getPriceBenchmarkSummary';

const usePriceBenchmarkSummary = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			summary: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default usePriceBenchmarkSummary;
