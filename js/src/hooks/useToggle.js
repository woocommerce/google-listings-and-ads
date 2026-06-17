/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * A hook to toggle a boolean state.
 *
 * @param {boolean} [initialValue=false] The initial value of the toggle.
 * @return {Array} A tuple of `[ value, toggle ]`.
 */
const useToggle = ( initialValue = false ) => {
	const [ value, setValue ] = useState( initialValue );
	const toggle = () => setValue( ( prev ) => ! prev );
	return [ value, toggle ];
};

export default useToggle;
