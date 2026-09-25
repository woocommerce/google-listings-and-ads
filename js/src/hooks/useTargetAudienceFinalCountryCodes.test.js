/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useTargetAudienceFinalCountryCodes from './useTargetAudienceFinalCountryCodes';
describe( 'useTargetAudienceFinalCountryCodes', () => {
	test( 'initially should return `{ loaded: false, data: undefined }`', () => {
		const { result } = renderHook( () =>
			useTargetAudienceFinalCountryCodes()
		);

		// assert initial state
		expect( result.current.loaded ).toBe( false );
		expect( result.current.data ).toBe( undefined );
	} );
} );
