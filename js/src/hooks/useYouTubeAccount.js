/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getYouTubeAccount';

const useYouTubeAccount = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			youTubeAccount: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useYouTubeAccount;
