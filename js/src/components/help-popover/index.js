/**
 * External dependencies
 */
import { Popover } from '@wordpress/components';
import { useState } from '@wordpress/element';
import GridiconHelpOutline from 'gridicons/dist/help-outline';

/**
 * Internal dependencies
 */
import recordTooltipViewedEvent from './recordTooltipViewedEvent';
import './index.scss';

/**
 * @param {Object} props React props
 * @param {string} props.id
 * @param {Array<JSX.Element>} props.children
 * @fires gla_tooltip_viewed with the given `id`.
 */
const HelpPopover = ( { id, children } ) => {
	const [ showPopover, setShowPopover ] = useState( false );

	const handleButtonClick = () => {
		setShowPopover( true );
		recordTooltipViewedEvent( id );
	};

	const handlePopoverClose = () => {
		setShowPopover( false );
	};

	return (
		<span className="help-popover">
			<button onClick={ handleButtonClick }>
				<GridiconHelpOutline size={ 16 }></GridiconHelpOutline>
			</button>
			{ showPopover && (
				<Popover
					focusOnMount="container"
					onClose={ handlePopoverClose }
				>
					{ children }
				</Popover>
			) }
		</span>
	);
};

export default HelpPopover;
