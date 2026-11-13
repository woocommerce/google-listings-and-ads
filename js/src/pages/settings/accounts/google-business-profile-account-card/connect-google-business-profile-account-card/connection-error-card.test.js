/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ConnectionErrorCard from './connection-error-card';
import { ERROR_SLOTS } from '~/data/constants';

jest.mock(
	'./connection-error-notice',
	() =>
		function MockConnectionErrorNotice( { errorSlots } ) {
			return <div>Error notice for { errorSlots.join( ', ' ) }</div>;
		}
);

describe( 'ConnectionErrorCard', () => {
	it( 'shows a "Not connected" badge and wires the connection-error notice into the error area', () => {
		render( <ConnectionErrorCard /> );

		expect( screen.getByText( 'Not connected' ) ).toBeInTheDocument();
		expect(
			screen.getByText(
				`Error notice for ${ ERROR_SLOTS.GOOGLE_BUSINESS_PROFILE_CONNECTION_ERROR_SLOT }`
			)
		).toBeInTheDocument();
	} );
} );
