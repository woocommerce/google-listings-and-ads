/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

export const AccountCreationContext = createContext( {} );

export function useAccountCreationContext() {
	const adaptiveFormContext = useContext( AccountCreationContext );

	if ( Object.keys( adaptiveFormContext ).length === 0 ) {
		throw new Error(
			'useAccountCreationContext was used outside of its context provider ConnectedGoogleComboAccountCard.'
		);
	}

	return adaptiveFormContext;
}
