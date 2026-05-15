/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getMCLanguagesCurrencies';

const useMCSupportedLanguages = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		// Trigger the shared resolver that populates both languages and currencies.
		selector[ selectorName ]();

		return {
			languages: selector.getMCSupportedLanguages(),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useMCSupportedLanguages;
