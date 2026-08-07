/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ConnectedSearchConsoleAccountCard from './connected-search-console-account-card';

describe( 'ConnectedSearchConsoleAccountCard', () => {
	it( 'renders the connected property URL and a connected indicator', () => {
		render(
			<ConnectedSearchConsoleAccountCard
				searchConsoleAccount={ {
					status: 'connected',
					property: { url: 'https://example.com/' },
				} }
			/>
		);

		expect(
			screen.getByText( 'https://example.com/' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Connected' ) ).toBeInTheDocument();
	} );
} );
