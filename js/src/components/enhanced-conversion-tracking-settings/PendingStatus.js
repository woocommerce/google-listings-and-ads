/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import { API_NAMESPACE } from '.~/data/constants';
import usePolling from '.~/hooks/usePolling';

const PendingStatus = () => {
	const { receiveAcceptedTerms } = useAppDispatch();

	const { data, start } = usePolling( {
		path: `${ API_NAMESPACE }/ads/accepted-customer-data-terms`,
	} );

	useEffect( () => {
		start();
	}, [ start ] );

	if ( ! data ) {
		return null;
	}

	if ( data.status !== null ) {
		receiveAcceptedTerms( data );
	}
};

export default PendingStatus;
