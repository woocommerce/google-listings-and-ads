/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import WPComAccountCard from './wpcom-account-card';
import useJetpackAccount from '~/hooks/useJetpackAccount';

jest.mock( '~/hooks/useJetpackAccount' );

describe( 'WPComAccountCard', () => {
	it( 'renders without crashing when WordPress.com is not connected', () => {
		useJetpackAccount.mockReturnValue( { jetpack: { active: 'no' } } );

		expect( () => render( <WPComAccountCard /> ) ).not.toThrow();
		expect( screen.queryByText( 'Connected' ) ).not.toBeInTheDocument();
	} );

	it( 'shows the owner email and badge when connected', () => {
		useJetpackAccount.mockReturnValue( {
			jetpack: {
				active: 'yes',
				owner: 'yes',
				email: 'merchant@example.com',
			},
		} );

		render( <WPComAccountCard /> );

		expect( screen.getByText( 'merchant@example.com' ) ).toBeVisible();
		expect( screen.getByText( 'Connected' ) ).toBeVisible();
	} );
} );
