/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';

/**
 * @return {{notifications: Array<Object>}} Notifications from the shared marketing notifications store.
 */
const useNotifications = () => {
	const notifications = useSelect( ( select ) => {
		return select( STORE_NAME ).getNotifications();
	}, [] );

	return { notifications };
};

export default useNotifications;
