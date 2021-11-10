/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import WarningModal from './warning-modal/warning-modal';
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';

const MODALS = Object.freeze( {
	NONE: 0,
	WARNING: 1,
} );

const CreateAccountButton = ( props ) => {
	const { onCreateAccount = noop, ...rest } = props;
	const [ activeModal, setActiveModal ] = useState( MODALS.NONE );
	const { existingAccounts } = useExistingGoogleMCAccounts();

	// TODO: logic for finding the real existing account.
	const existingAccount = existingAccounts.find( ( el ) => {
		return el.id === 0;
	} );

	const handleCreateAccountClick = () => {
		if ( ! existingAccount ) {
			onCreateAccount();
			return;
		}

		setActiveModal( MODALS.WARNING );
	};

	const handleWarningContinue = () => {
		onCreateAccount();
	};

	const handleModalRequestClose = () => {
		setActiveModal( MODALS.NONE );
	};

	return (
		<>
			<Button
				isSecondary
				onClick={ handleCreateAccountClick }
				{ ...rest }
			>
				{ __(
					'Or, create a new Merchant Center account',
					'google-listings-and-ads'
				) }
			</Button>
			{ activeModal === MODALS.WARNING && (
				<WarningModal
					existingAccount={ existingAccount }
					onContinue={ handleWarningContinue }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
		</>
	);
};

export default CreateAccountButton;
