/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { Popover } from 'extracted/@wordpress/components';
import { useState } from '@wordpress/element';
import GridiconHelpOutline from 'gridicons/dist/help-outline';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Viewing tooltip
 *
 * @event gla_tooltip_viewed
 * @property {string} id Tooltip identifier.
 */

/**
 * @param {Object} props React props
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {string} [props.id] The Popover’s ID for event tracking.
 * @param {boolean} [props.disabled=false] Whether to disable the help icon button and also hide the popover.
 * @param {number} [props.iconSize=16] Size of the help icon.
 * @param {Array<JSX.Element>} props.children The Popover’s content
 * @fires gla_tooltip_viewed with the given `id`.
 */
const HelpPopover = ( {
	className,
	id,
	disabled = false,
	iconSize = 16,
	children,
	...props
} ) => {
	const [ showPopover, setShowPopover ] = useState( false );

	const handleButtonClick = () => {
		setShowPopover( true );

		if ( id ) {
			recordEvent( 'gla_tooltip_viewed', { id } );
		}
	};

	const handlePopoverClose = () => {
		setShowPopover( false );
	};

	return (
		<span className={ classnames( 'help-popover', className ) }>
			<button
				aria-label={ __( 'Open popover', 'google-listings-and-ads' ) }
				disabled={ disabled }
				onClick={ handleButtonClick }
			>
				<GridiconHelpOutline size={ iconSize }></GridiconHelpOutline>
			</button>
			{ showPopover && ! disabled && (
				<Popover
					focusOnMount="container"
					onClose={ handlePopoverClose }
					{ ...props }
				>
					{ children }
				</Popover>
			) }
		</span>
	);
};

export default HelpPopover;
