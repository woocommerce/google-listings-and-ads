/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

export const AccountCreationContext = createContext( false );

export function useAccountCreationContext() {
	const adaptiveFormContext = useContext( AccountCreationContext );

	if ( adaptiveFormContext === false ) {
		throw new Error(
			'useAccountCreationContext was used outside of its context provider.'
		);
	}

	return adaptiveFormContext;
}
