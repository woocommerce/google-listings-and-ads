/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SingleBusinessProfileLocationNotice from './single-business-profile-location-notice';
import useGoogleAccount from '~/hooks/useGoogleAccount';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);

const location = {
	id: '1111',
	title: "Jane's Bakery",
	address: '2423 1st Ave, Seattle, WA, 98121',
};

describe( 'SingleBusinessProfileLocationNotice', () => {
	it( "renders the location's address and the create-new-location link", () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );

		render(
			<SingleBusinessProfileLocationNotice
				location={ location }
				createLocationNotice={ null }
				onCreateLocationClick={ jest.fn() }
			/>
		);

		expect(
			screen.getByText( '2423 1st Ave, Seattle, WA, 98121', {
				selector: 'p',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: 'Create new location (opens in a new tab)',
			} )
		).toHaveAttribute( 'href', 'https://business.google.com/create' );
	} );

	it( 'renders the refresh reminder above the create-new-location link once given one', () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );

		const { container } = render(
			<SingleBusinessProfileLocationNotice
				location={ location }
				createLocationNotice={ <div>Refresh reminder</div> }
				onCreateLocationClick={ jest.fn() }
			/>
		);

		expect(
			container.textContent.indexOf( 'Refresh reminder' )
		).toBeLessThan(
			container.textContent.indexOf( 'Create new location' )
		);
	} );
} );
