/**
 * External dependencies
 */
import { Tooltip } from 'extracted/@wordpress/components';

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

	return (
		<Tooltip { ...rest }>
			{ /*
			This inline-block div is needed for the tooltip to show up correctly.
			If we use span, the tooltip will not wrap ToggleControl nicely.
			*/ }
			<div className="gla-tooltip__children-container">{ children }</div>
		</Tooltip>
	);
};

export default AppTooltip;
