/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import NoContainerFound from './index';

jest.mock( '../create-new-container-button', () => () => (
	<button>Mock create new container</button>
) );
jest.mock( '../create-container-notice', () => () => (
	<p>Mock create container notice</p>
) );

describe( 'NoContainerFound', () => {
	it( 'says that no container was found instead of showing a selector', () => {
		render( <NoContainerFound /> );

		expect( screen.getByText( 'No container found' ) ).toBeInTheDocument();
		expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Save' } )
		).not.toBeInTheDocument();
	} );

	it( 'offers to create a container and explains a read-only connection', () => {
		render( <NoContainerFound /> );

		expect(
			screen.getByRole( 'button', {
				name: 'Mock create new container',
			} )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Mock create container notice' )
		).toBeInTheDocument();
	} );
} );
