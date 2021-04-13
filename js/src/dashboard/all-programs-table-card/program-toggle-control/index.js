/**
 * External dependencies
 */
import { ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import PauseProgramModal from './pause-program-modal';
import './index.scss';

const ProgramToggleControl = ( props ) => {
	const { program } = props;
	const [ checked, setChecked ] = useState( program.active );
	const [ showModal, setShowModal ] = useState( false );

	const handleChange = ( v ) => {
		if ( v === false ) {
			setShowModal( true );
			return;
		}

		setChecked( v );
		// TODO: fire API request to enable campaign.
	};

	const handleModalRequestClose = () => {
		setShowModal( false );
	};

	const handleKeepActive = () => {
		setShowModal( false );
	};

	const handlePauseCampaign = () => {
		setShowModal( false );
		setChecked( false );
		// TODO: fire api request to pause campaign.
	};

	return (
		<>
			<ToggleControl
				className="gla-program-toggle-control"
				checked={ checked }
				onChange={ handleChange }
			/>
			{ showModal && (
				<PauseProgramModal
					programId={ program.id }
					onKeepActive={ handleKeepActive }
					onPauseCampaign={ handlePauseCampaign }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
		</>
	);
};

export default ProgramToggleControl;
