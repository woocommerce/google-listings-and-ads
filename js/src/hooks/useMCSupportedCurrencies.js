/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getMCLanguagesCurrencies';

const useMCSupportedCurrencies = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		// Trigger the shared resolver that populates both languages and currencies.
		selector[ selectorName ]();

		return {
			currencies: selector.getMCSupportedCurrencies(),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useMCSupportedCurrencies;
