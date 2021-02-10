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

/**
 * Automatically save settings value upon value change with a debounce delay.
 * It does not save on first render since the first render is the loading of value from API.
 *
 * @param {Object} value Settings value object to be saved.
 */
const useAutoSaveSettingsEffect = ( value ) => {
	const { saveSettings } = useAppDispatch();
	const debouncedCallback = useDebouncedCallback( saveSettings, delay );
	const ref = useRef( null );

	useEffect( () => {
		// do not save on first render.
		if ( ! ref.current ) {
			ref.current = value;
			return;
		}

		// call the debounced callback.
		debouncedCallback.callback( value );
	}, [ debouncedCallback, value ] );
};

export default useAutoSaveSettingsEffect;
