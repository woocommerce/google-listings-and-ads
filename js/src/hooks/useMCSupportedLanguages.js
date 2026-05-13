/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getMCSupportedLanguages';

const useMCSupportedLanguages = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			languages: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useMCSupportedLanguages;
