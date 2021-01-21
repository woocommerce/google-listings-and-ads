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

const HelpPopover = ( props ) => {
	const { id, children } = props;
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
