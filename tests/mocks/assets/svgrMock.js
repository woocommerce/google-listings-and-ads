/**
 * External dependencies
 */
import { forwardRef } from 'react';

export const ReactComponent = forwardRef( ( props, ref ) => (
	<span ref={ ref } { ...props } />
) );

export default 'SvgrURL';
