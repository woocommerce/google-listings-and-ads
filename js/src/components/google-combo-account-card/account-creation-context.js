/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

/**
 * @typedef {Object} AccountCreationContext
 * @property {boolean} accountsCreated `true` if the accounts have been created.
 * @property {string|null} creatingAccounts The accounts that are being created. It can be 'ads', 'mc', or 'both'.
 */

export const AccountCreationContext = createContext( {} );

/**
 * AccountCreation's context hook.
 * @return {Object} AccountCreation's context.
 * @throws Will throw an error if its context provider is not existing in its parents.
 */
export function useAccountCreationContext() {
	const adaptiveFormContext = useContext( AccountCreationContext );

	if ( Object.keys( adaptiveFormContext ).length === 0 ) {
		throw new Error(
			'useAccountCreationContext was used outside of its context provider ConnectedGoogleComboAccountCard.'
		);
	}

	return adaptiveFormContext;
}
