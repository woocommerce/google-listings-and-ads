/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ActionNeededPropertySelection, {
	actionNeededNotice,
} from './action-needed-property-selection';
import PropertySelection from './property-selection';

jest.mock( './property-selection', () =>
	jest
		.fn( () => <div>PropertySelection</div> )
		.mockName( 'PropertySelection' )
);

describe( 'ActionNeededPropertySelection', () => {
	it( 'always keeps the create action visible, even with no other candidates', () => {
		render( <ActionNeededPropertySelection /> );

		expect( PropertySelection ).toHaveBeenCalledWith(
			expect.objectContaining( { alwaysShowCreateAction: true } ),
			expect.anything()
		);
	} );
} );

describe( 'actionNeededNotice', () => {
	it( 'uses the same warning status and title regardless of candidates', () => {
		expect( actionNeededNotice( true ) ).toMatchObject( {
			status: 'warning',
			title: 'Your Search Console property needs attention',
		} );
		expect( actionNeededNotice( false ) ).toMatchObject( {
			status: 'warning',
			title: 'Your Search Console property needs attention',
		} );
	} );

	it( 'points to the selector when other candidates are available', () => {
		expect( actionNeededNotice( true ).body ).toMatch(
			/select another property below/i
		);
	} );

	it( "doesn't reference a selector when no other candidates are available", () => {
		expect( actionNeededNotice( false ).body ).not.toMatch(
			/select another property below/i
		);
	} );
} );
