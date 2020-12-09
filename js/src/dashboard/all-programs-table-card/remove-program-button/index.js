/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RemoveProgramModal from './remove-program-modal';

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
		<>
			<Button isDestructive isTertiary isLink onClick={ handleClick }>
				{ __( 'Remove', 'google-listings-and-ads' ) }
			</Button>
			{ isOpen && (
				<RemoveProgramModal
					programId={ programId }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
		</>
	);
};

export default RemoveProgramButton;
