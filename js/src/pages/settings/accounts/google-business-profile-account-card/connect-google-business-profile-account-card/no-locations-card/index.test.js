/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import NoLocationsCard from './index';
import { useAppDispatch } from '~/data';
import useGoogleAccount from '~/hooks/useGoogleAccount';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);

describe( 'NoLocationsCard', () => {
	it( 'shows an "Action needed" badge and the zero-locations notice', () => {
		useAppDispatch.mockReturnValue( {
			fetchGoogleBusinessProfileAccount: jest.fn(),
			fetchGoogleBusinessProfileLocations: jest.fn(),
		} );
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );

		render( <NoLocationsCard /> );

		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.getByText( /We couldn't find a Google Business Profile/, {
				selector: 'p',
			} )
		).toBeInTheDocument();
	} );
} );
