/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getAvailableLanguagesCurrencies';

const useAvailableStoreCurrencies = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		// Trigger the shared resolver that populates both languages and currencies.
		selector[ selectorName ]();

		return {
			currencies: selector.getAvailableStoreCurrencies(),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useAvailableStoreCurrencies;
