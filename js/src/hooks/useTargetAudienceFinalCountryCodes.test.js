/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useTargetAudienceFinalCountryCodes from './useTargetAudienceFinalCountryCodes';
describe( 'useTargetAudienceFinalCountryCodes', () => {
	test( 'initially should return `{ loading: false, data: undefined }`', () => {
		const { result } = renderHook( () =>
			useTargetAudienceFinalCountryCodes()
		);

		// assert initial state
		expect( result.current.loading ).toBe( false );
		expect( result.current.data ).toBe( undefined );
	} );
} );
