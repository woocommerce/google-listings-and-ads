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
import WarningModal from '../warning-modal';
import TermsModal from '../terms-modal';
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import getMatchingDomainAccount from '../getMatchingDomainAccount';

const MODALS = Object.freeze( {
	NONE: 'NONE',
	WARNING: 'WARNING',
	TERMS: 'TERMS',
} );

const CreateAccountButton = ( props ) => {
	const { onCreateAccount = noop, ...rest } = props;
	const [ activeModal, setActiveModal ] = useState( MODALS.NONE );
	const { data: existingAccounts } = useExistingGoogleMCAccounts();
	const matchingDomainAccount = getMatchingDomainAccount( existingAccounts );

	const handleCreateAccountClick = () => {
		setActiveModal( matchingDomainAccount ? MODALS.WARNING : MODALS.TERMS );
	};

	const handleWarningContinue = () => {
		setActiveModal( MODALS.TERMS );
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
					existingAccount={ matchingDomainAccount }
					onContinue={ handleWarningContinue }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
			{ activeModal === MODALS.TERMS && (
				<TermsModal
					onCreateAccount={ onCreateAccount }
					onRequestClose={ handleModalRequestClose }
				/>
			) }
		</>
	);
};

export default CreateAccountButton;
