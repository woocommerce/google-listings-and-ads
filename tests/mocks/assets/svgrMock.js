/**
 * External dependencies
 */
import { createElement, forwardRef } from '@wordpress/element';

const SvgMock = forwardRef( ( props, ref ) =>
	createElement( 'svg', { ref, ...props } )
);

// Common SVGR compatibility: named export ReactComponent
export const ReactComponent = SvgMock;

// Default export should be the component for `?inline` usage
export default SvgMock;
