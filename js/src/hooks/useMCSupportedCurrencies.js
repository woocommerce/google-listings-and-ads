/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getMCSupportedCurrencies';

const useMCSupportedCurrencies = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			currencies: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useMCSupportedCurrencies;
