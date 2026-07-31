/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../data/constants';

const useDismissNotification = () => {
	const { dismissNotification } = useDispatch( STORE_KEY );

	return dismissNotification;
};

export default useDismissNotification;
