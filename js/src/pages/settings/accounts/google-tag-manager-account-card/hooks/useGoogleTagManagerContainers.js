/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

/**
 * @typedef {import('~/data/types.js').GoogleTagManagerContainer} GoogleTagManagerContainer
 */

const selectorName = 'getGoogleTagManagerContainers';

/**
 * A hook to load the containers belonging to the connected Google Tag Manager account.
 *
 * @return {{ containers: GoogleTagManagerContainer[]|null, hasFinishedResolution: boolean }} The data and its resolution state.
 */
const useGoogleTagManagerContainers = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			containers: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useGoogleTagManagerContainers;
