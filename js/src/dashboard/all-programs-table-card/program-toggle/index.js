/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
/**
 * Internal dependencies
 */
import PauseProgramModal from './pause-program-modal';
import { useAppDispatch } from '.~/data';
import AppStandaloneToggleControl from '.~/components/app-standalone-toggle-control';

const ProgramToggle = ( props ) => {
	const { program } = props;
	const [ checked, setChecked ] = useState( program.active );
	const [ showModal, setShowModal ] = useState( false );
	const { updateAdsCampaign } = useAppDispatch();

	const handleChange = ( v ) => {
		if ( v === false ) {
			setShowModal( true );
			return;
		}

		setChecked( v );
		updateAdsCampaign( program.id, { status: 'enabled' } );
	};

	const handleModalRequestClose = () => {
		setShowModal( false );
	};

	const handlePauseCampaign = () => {
		setShowModal( false );
		setChecked( false );
		updateAdsCampaign( program.id, { status: 'paused' } );
	};

	return (
		<>
			<AppStandaloneToggleControl
				checked={ checked }
				onChange={ handleChange }
			/>
			{ showModal && (
				<PauseProgramModal
					programId={ program.id }
					onPauseCampaign={ handlePauseCampaign }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
		</>
	);
};

export default ProgramToggle;
