/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { doAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '../data/constants';

const useDismissNotification = () => {
	const { dismissNotification } = useDispatch( STORE_KEY );

	return ( id ) => {
		dismissNotification( id );
		doAction( 'gla_notification_dismissed' );
	};
};

export default useDismissNotification;
