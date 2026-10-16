/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getGenAITextAssets';

const useGenAITextAssets = ( url, assetType ) => {
	return useSelect(
		( select ) => {
			const assets = select( STORE_KEY )[ selectorName ](
				url,
				assetType
			);

			return {
				assets,
			};
		},
		[ url, assetType ]
	);
};

export default useGenAITextAssets;
