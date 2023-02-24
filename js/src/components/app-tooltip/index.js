/**
 * External dependencies
 */
import { Tooltip } from 'extracted/@wordpress/components';
import { Children } from '@wordpress/element';

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

	// `Tooltip` component will create an event catcher floating upon the child when it finds
	// the child is disabled. Therefore, this component needs to forward the `disabled` prop
	// of the actual child to the wrapper <div> in order to make `Tooltip` able to catch the
	// `onMouseLeave` event and close the popover properly.
	//
	// Ref:
	// - https://github.com/WordPress/gutenberg/blob/%40wordpress/components%4019.17.0/packages/components/src/tooltip/index.js#L253-L257
	// - https://github.com/WordPress/gutenberg/blob/%40wordpress/components%4019.17.0/packages/components/src/tooltip/index.js#L35-L51
	let childDisabled;
	const renderingChildren = Children.toArray( children );
	if ( renderingChildren.length === 1 ) {
		childDisabled = renderingChildren[ 0 ].props?.disabled;
	}

	return (
		<Tooltip { ...rest }>
			{ /*
			This inline-block div is needed for the tooltip to show up correctly.
			If we use span, the tooltip will not wrap ToggleControl nicely.
			*/ }
			<div
				className="gla-tooltip__children-container"
				disabled={ childDisabled }
			>
				{ children }
			</div>
		</Tooltip>
	);
};

export default AppTooltip;
