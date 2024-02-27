/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

export default function usePrevious( state ) {
	const ref = useRef();

	useEffect( () => {
		ref.current = state;
	} );

	return ref.current;
}
