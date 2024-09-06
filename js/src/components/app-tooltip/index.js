/**
 * External dependencies
 */
import { Tooltip } from '@wordpress/components';
import { Children } from '@wordpress/element';
import { isWpVersion } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved

/**
 * Internal dependencies
 */
import './index.scss';

// It's an inverse map of the following reference and contains only the values used in this repo.
// Ref: https://github.com/WordPress/gutenberg/blob/wp/6.4/packages/components/src/popover/utils.ts#L17-L71
const PLACEMENT_TO_POSITION = {
	'top-start': 'top right',
	top: 'top center',
};

// compatibility-code "WP < 6.4" -- `position` prop in Tooltip is deprecated since WP 6.4
function mapPlacementToPosition( props ) {
	if ( isWpVersion( '6.4', '<' ) ) {
		const { placement, ...restProps } = props;
		const position = PLACEMENT_TO_POSITION[ placement ];

		if ( position ) {
			return { ...restProps, position };
		}
	}

	return props;
}

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
		<Tooltip { ...mapPlacementToPosition( rest ) }>
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
