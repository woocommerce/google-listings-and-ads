/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
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
			<AppButton isDestructive isLink onClick={ handleClick }>
				{ __( 'Remove', 'google-listings-and-ads' ) }
			</AppButton>
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
