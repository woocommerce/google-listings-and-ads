/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useAcceptedCustomerDataTerms from './useAcceptedCustomerDataTerms';

describe( 'useAcceptedCustomerDataTerms', () => {
	it( 'Returns the correct status when set to true', () => {
		const { result } = renderHook( () => {
			const { receiveAcceptedTerms } = useAppDispatch();
			receiveAcceptedTerms( { status: true } );

			return useAcceptedCustomerDataTerms();
		} );

		expect( result.current.acceptedCustomerDataTerms ).toBeTruthy();
	} );
} );
