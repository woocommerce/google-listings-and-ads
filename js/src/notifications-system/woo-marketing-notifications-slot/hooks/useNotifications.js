/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../data/constants';

/**
 * @typedef {import('../data/selectors').Notification} Notification
 */

/**
 * @return {{notifications: Notification[]}} Notifications from the shared marketing notifications store.
 */
const useNotifications = () => {
	const notifications = useSelect( ( select ) => {
		return select( STORE_KEY ).getNotifications();
	}, [] );

	return { notifications };
};

export default useNotifications;
