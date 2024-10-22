/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

/**
 * @typedef {Object} AccountCreationContext
 * @property {boolean} accountsCreated - `true` if the accounts have been created, `false` otherwise.
 * @property {('ads'|'mc'|'both'|null)} creatingWhich - The accounts that are being created.
 */

/**
 * @type {AccountCreationContext}
 */
const defaultContext = {
	accountsCreated: undefined,
	creatingWhich: undefined,
};

export const AccountCreationContext = createContext( defaultContext );

/**
 * AccountCreation's context hook.
 * @return {AccountCreationContext} The current context value for AccountCreation.
 * @throws Will throw an error if the context provider is missing in the component's ancestors.
 */
export function useAccountCreationContext() {
	const adaptiveFormContext = useContext( AccountCreationContext );

	if (
		adaptiveFormContext.accountsCreated === undefined ||
		adaptiveFormContext.creatingWhich === undefined
	) {
		throw new Error(
			'useAccountCreationContext was used outside of its context provider ConnectedGoogleComboAccountCard.'
		);
	}

	return adaptiveFormContext;
}
