/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ConnectAccountCard from './connect-account-card';

describe( 'ConnectAccountCard', () => {
	it( 'renders the shared Google Business Profile appearance with the given indicator and detail', () => {
		render(
			<ConnectAccountCard
				indicator={ <button>My indicator</button> }
				detail={ <p>My detail</p> }
			/>
		);

		expect(
			screen.getByText( 'Google Business Profile' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'My indicator' } )
		).toBeInTheDocument();
		expect( screen.getByText( 'My detail' ) ).toBeInTheDocument();
	} );
} );
