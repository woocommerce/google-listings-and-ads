/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '../../../data';

const delay = 500;

const useAutoSaveSettingsEffect = ( value ) => {
	const { saveSettings } = useAppDispatch();
	const debouncedCallback = useDebouncedCallback( saveSettings, delay );
	const ref = useRef( null );

	useEffect( () => {
		if ( ! ref.current ) {
			ref.current = value;
			return;
		}

		debouncedCallback.callback( value );
	}, [ debouncedCallback, value ] );
};

export default useAutoSaveSettingsEffect;
