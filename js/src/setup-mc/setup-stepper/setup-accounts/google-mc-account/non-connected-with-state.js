/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

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

const getMatchingDomainAccount = ( existingAccounts = [] ) => {
	const homeUrl = getSetting( 'homeUrl' );

	return existingAccounts.find( ( el ) => el.domain === homeUrl );
};

/**
 * A component that handles the state to display the card and modal flow.
 *
 * @param {Object} props Props.
 * @param {Array<Object>} props.existingAccounts Existing merchant center accounts.
 */
const NonConnectedWithState = ( props ) => {
	const { existingAccounts } = props;
	const matchingDomainAccount = getMatchingDomainAccount( existingAccounts );

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
			return (
				<ConnectMCCard
					onCreateNew={ () =>
						setModal(
							matchingDomainAccount
								? MODALS.WARNING
								: MODALS.TERMS
						)
					}
				/>
			);
		}

		return null;
	} )( card );

	const modalUI = ( ( val ) => {
		if ( val === MODALS.WARNING ) {
			return (
				<WarningModal
					existingAccount={ matchingDomainAccount }
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
