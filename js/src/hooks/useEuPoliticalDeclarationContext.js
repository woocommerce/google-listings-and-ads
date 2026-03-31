/**
 * External dependencies
 */
import { useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EuPoliticalDeclarationContext } from '~/components/eu-political-declaration/eu-political-declaration-provider';

/**
 * Hook to access the EU political declaration modal context.
 *
 * @return {EuPoliticalDeclarationContext} The context value with `showModal`, `hideModal` and `handleError` functions.
 * @throws Will throw an error if used outside of `EuPoliticalDeclarationProvider`.
 */
export default function useEuPoliticalDeclarationContext() {
	const context = useContext( EuPoliticalDeclarationContext );

	if ( context === null ) {
		throw new Error(
			'useEuPoliticalDeclarationContext was used outside of its context provider EuPoliticalDeclarationProvider.'
		);
	}

	return context;
}
