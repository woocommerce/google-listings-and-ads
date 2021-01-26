/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { useDebouncedCallback } from 'use-debounce';

const DebounceCallback = ( props ) => {
	const { value, delay, onCallback = () => {} } = props;
	const ref = useRef( null );
	const debouncedCallback = useDebouncedCallback( onCallback, delay );

	useEffect( () => {
		if ( ! ref.current ) {
			ref.current = value;
			return;
		}

		debouncedCallback.callback( value );
	}, [ debouncedCallback, value ] );

	return null;
};

export default DebounceCallback;
