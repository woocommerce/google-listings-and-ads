/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import PauseProgramModal from './pause-program-modal';

const PauseProgramButton = ( props ) => {
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
			<Button isTertiary isLink onClick={ handleClick }>
				{ __( 'Pause', 'google-listings-and-ads' ) }
			</Button>
			{ isOpen && (
				<PauseProgramModal
					programId={ programId }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
		</>
	);
};

export default PauseProgramButton;
