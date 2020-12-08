/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import RemoveProgramModal from './remove-program-modal';
import './index.scss';

const RemoveProgramButton = ( props ) => {
	const { programId } = props;
	const [ isOpen, setOpen ] = useState( false );

	const handleClick = () => {
		setOpen( true );
	};

	const handleModalRequestClose = () => {
		setOpen( false );
	};

	return (
		<span className="gla-remove-program-button">
			<Button isDestructive isTertiary isLink onClick={ handleClick }>
				Remove
			</Button>
			{ isOpen && (
				<RemoveProgramModal
					programId={ programId }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
		</span>
	);
};

export default RemoveProgramButton;
