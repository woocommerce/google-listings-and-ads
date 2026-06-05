/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const selectorName = 'getNotifications';

/**
 * @typedef {import('~/data/selectors').Notification} Notification
 */

/**
 * @return {{ notifications: Array<Notification>, hasFinishedResolution: boolean }} Current notifications from the store.
 */
const useNotifications = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			notifications: selector[ selectorName ](),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	}, [] );
};

export default useNotifications;
