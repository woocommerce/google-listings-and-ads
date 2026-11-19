/**
 * External dependencies
 */
import { useState, useCallback } from '@wordpress/element';

/**
 * A hook to hold a boolean state that can also be toggled.
 *
 * Returns the same `[ value, setValue ]` pair as `useState`, plus a `toggle`
 * function last, so callers that only need the setter can omit it.
 *
 * @param {boolean} [initialValue=false] The initial value of the toggle.
 * @return {Array} `[ value, setValue, toggle ]`: the current boolean, the state setter, and a function that flips the value.
 */
const useToggle = ( initialValue = false ) => {
	const [ value, setValue ] = useState( initialValue );
	const toggle = useCallback( () => setValue( ( prev ) => ! prev ), [] );
	return [ value, setValue, toggle ];
};

export default useToggle;
