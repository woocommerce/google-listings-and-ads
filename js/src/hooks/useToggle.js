/**
 * External dependencies
 */
import { useState, useCallback } from '@wordpress/element';

/**
 * A hook to toggle a boolean state.
 *
 * The setter is also returned so that callers can set an explicit value,
 * e.g. `setValue( false )` in a modal's `onRequestClose`. Unlike `toggle`,
 * it is safe to call repeatedly without flipping the state back.
 *
 * @param {boolean} [initialValue=false] The initial value of the toggle.
 * @return {Array} `[ value, toggle, setValue ]`: the current boolean, a function that flips it, and the state setter.
 */
const useToggle = ( initialValue = false ) => {
	const [ value, setValue ] = useState( initialValue );
	const toggle = useCallback( () => setValue( ( prev ) => ! prev ), [] );
	return [ value, toggle, setValue ];
};

export default useToggle;
