/**
 * External dependencies
 */
import classnames from 'classnames';
import { Tooltip } from 'extracted/@wordpress/components';
import { Children, isValidElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * A convenient wrapper around Tooltip component, so that you don't need to specify your own children span or div.
 *
 * ## Usage
 *
 * ```js
	<AppTooltip
		text="Help text here."
	>
		<ToggleControl />
	</AppTooltip>
 * ```
 *
 * @param {Object} props Tooltip props.
 */
const AppTooltip = ( props ) => {
	const { children, ...rest } = props;
	const containerClasses = [ 'gla-tooltip__children-container' ];

	// `Tooltip` component won't attach an `onMouseLeave` handler to a disabled child to
	// hide the popover. It may result in leaving the popover still open after the mouse
	// moves out of the disabled child.
	//
	// Ref: https://github.com/WordPress/gutenberg/blob/%40wordpress/components%4019.17.0/packages/components/src/tooltip/index.js#L193-L201
	//
	// Although this component wraps a <div> as the passed child to make `Tooltip` bind
	// the `onMouseLeave` handler to the wrapper <div> instead, a disabled child element
	// such as <button> still won't fire the `onMouseLeave` event nor further propagate
	// to the wrapper.
	//
	// Therefore, here uses a trick that adds an additional placeholder to float upon and
	// cover the whole disabled child, so that it will be possible to directly trigger the
	// `onMouseLeave` event from the wrapper.
	const renderingChildren = Children.toArray( children );
	if ( renderingChildren.length === 1 ) {
		const child = renderingChildren[ 0 ];

		if ( isValidElement( child ) && child.props.disabled ) {
			const baseClass = containerClasses[ 0 ];
			containerClasses.push( `${ baseClass }--hover-placeholder` );
		}
	}

	return (
		<Tooltip { ...rest }>
			{ /*
			This inline-block div is needed for the tooltip to show up correctly.
			If we use span, the tooltip will not wrap ToggleControl nicely.
			*/ }
			<div className={ classnames( containerClasses ) }>{ children }</div>
		</Tooltip>
	);
};

export default AppTooltip;
