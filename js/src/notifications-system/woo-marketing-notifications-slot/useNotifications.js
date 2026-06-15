/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

const STORE_NAME = 'woocommerce/marketing-notifications-system';

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
