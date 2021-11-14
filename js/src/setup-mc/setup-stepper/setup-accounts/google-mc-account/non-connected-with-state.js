/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ConnectMCCard from './connect-mc-card';
import CreateAccount from './create-account';
import WarningModal from './warning-modal';
import TermsModal from './terms-modal';

const CARDS = Object.freeze( {
	CONNECT: 'CONNECT',
	CREATE: 'CREATE',
	CREATING: 'CREATING',
} );

const MODALS = Object.freeze( {
	NONE: 'NONE',
	WARNING: 'WARNING',
	TERMS: 'TERMS',
} );

/**
 * A component that handles the state to display the card and modal flow.
 *
 * @param {Object} props Props.
 * @param {Array<Object>} props.existingAccounts Existing merchant center accounts.
 */
const NonConnectedWithState = ( props ) => {
	const { existingAccounts } = props;

	/**
	 * When we first load, we will only be in one of two possible states,
	 * either create or connect, depending on whether we have existing accounts.
	 */
	const [ card, setCard ] = useState(
		existingAccounts.length === 0 ? CARDS.CREATE : CARDS.CONNECT
	);
	const [ modal, setModal ] = useState( MODALS.NONE );

	const cardUI = ( ( val ) => {
		if ( val === CARDS.CREATE ) {
			return (
				<CreateAccount
					allowShowExisting={ existingAccounts.length > 0 }
					onShowExisting={ () => setCard( CARDS.CONNECT ) }
				/>
			);
		}

		if ( val === CARDS.CONNECT ) {
			// TODO: optionally display warning modal, and then display terms modal.
			// should not change to CREATE card.
			return (
				<ConnectMCCard onCreateNew={ () => setCard( CARDS.CREATE ) } />
			);
		}

		return null;
	} )( card );

	const modalUI = ( ( val ) => {
		if ( val === MODALS.WARNING ) {
			// TODO: logic for finding the real existing account.
			const existingAccount = existingAccounts.find( ( el ) => {
				return el.id === 412140014;
			} );

			return (
				<WarningModal
					existingAccount={ existingAccount }
					onContinue={ () => setModal( MODALS.TERMS ) }
					onRequestClose={ () => setModal( MODALS.NONE ) }
				/>
			);
		}

		if ( val === MODALS.TERMS ) {
			return (
				<TermsModal
					onCreateAccount={ () => {
						// TODO: process of creating MC account here.
					} }
					onRequestClose={ () => setModal( MODALS.NONE ) }
				/>
			);
		}

		return null;
	} )( modal );

	return (
		<>
			{ cardUI }
			{ modalUI }
		</>
	);
};

export default NonConnectedWithState;
