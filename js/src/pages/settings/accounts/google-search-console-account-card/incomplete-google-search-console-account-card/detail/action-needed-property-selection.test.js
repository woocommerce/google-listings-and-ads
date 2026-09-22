/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ActionNeededPropertySelection from './action-needed-property-selection';
import PropertySelection from './property-selection';

jest.mock( './property-selection', () =>
	jest
		.fn( () => <div>PropertySelection</div> )
		.mockName( 'PropertySelection' )
);

describe( 'ActionNeededPropertySelection', () => {
	it( 'renders PropertySelection with actionNeeded set', () => {
		render( <ActionNeededPropertySelection /> );

		expect( PropertySelection ).toHaveBeenCalledWith(
			expect.objectContaining( { actionNeeded: true } ),
			expect.anything()
		);
	} );
} );
