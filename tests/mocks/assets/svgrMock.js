/**
 * External dependencies
 */
import { forwardRef } from '@wordpress/element';

export const ReactComponent = forwardRef( ( props, ref ) => (
	<span ref={ ref } { ...props } />
) );

export default 'SvgrURL';
