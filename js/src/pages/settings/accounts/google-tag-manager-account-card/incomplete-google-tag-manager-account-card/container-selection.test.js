/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ContainerSelection from './container-selection';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useGoogleTagManagerContainers from '../hooks/useGoogleTagManagerContainers';
import useConnectGoogleTagManagerContainer from '../hooks/useConnectGoogleTagManagerContainer';

jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( '../hooks/useGoogleTagManagerContainers', () =>
	jest.fn().mockName( 'useGoogleTagManagerContainers' )
);
jest.mock( '../hooks/useConnectGoogleTagManagerContainer', () =>
	jest.fn().mockName( 'useConnectGoogleTagManagerContainer' )
);

/**
 * Mocks `useGoogleTagManagerContainers` (the candidate containers list).
 *
 * @param {Object[]} [containers] The containers to mock.
 * @param {boolean} [hasFinishedResolution] Whether the resolver has finished.
 */
function mockContainers( containers, hasFinishedResolution = true ) {
	useGoogleTagManagerContainers.mockReturnValue( {
		containers,
		hasFinishedResolution,
	} );
}

describe( 'ContainerSelection', () => {
	beforeEach( () => {
		jest.clearAllMocks();

		useGoogleTagManagerAccount.mockReturnValue( {
			account: {
				status: 'incomplete',
				id: '6002847391',
				name: 'Enjoy Mommyhood',
			},
			hasFinishedResolution: true,
		} );
		useConnectGoogleTagManagerContainer.mockReturnValue( {
			selectContainer: jest.fn().mockName( 'selectContainer' ),
			loading: false,
		} );
	} );

	it( 'renders nothing until the containers list has resolved', () => {
		mockContainers( undefined, false );

		const { container } = render( <ContainerSelection /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'replaces the selector with the CTA when the account has zero containers', () => {
		mockContainers( [] );

		render( <ContainerSelection /> );

		expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Save' } )
		).not.toBeInTheDocument();

		expect(
			screen.getByRole( 'link', {
				name: 'Create new container (opens in a new tab)',
			} )
		).toHaveAttribute( 'href', 'https://tagmanager.google.com/' );
	} );

	it( 'shows the CTA inline beside the selector when the account already has containers', () => {
		mockContainers( [
			{ id: '98765432', publicId: 'GTM-PR99HWXX', name: 'woo' },
		] );

		render( <ContainerSelection /> );

		expect( screen.getByRole( 'combobox' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Save' } )
		).toBeInTheDocument();

		expect(
			screen.getByRole( 'link', {
				name: 'Create new container (opens in a new tab)',
			} )
		).toHaveAttribute( 'href', 'https://tagmanager.google.com/' );
	} );
} );
