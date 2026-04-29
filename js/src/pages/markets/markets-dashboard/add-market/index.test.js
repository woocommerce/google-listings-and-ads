/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import AddMarket from './';

jest.mock( '../add-market-modal', () =>
	jest.fn( ( { onRequestClose } ) => (
		<div data-testid="add-market-modal">
			<button onClick={ onRequestClose }>Close modal</button>
		</div>
	) )
);

describe( 'AddMarket', () => {
	test( 'renders the primary "Add market" button', () => {
		render( <AddMarket /> );

		const button = screen.getByRole( 'button', { name: 'Add market' } );
		expect( button ).toBeInTheDocument();
		expect( button ).toHaveClass( 'is-primary' );
	} );

	test( 'does not render the modal until the button is clicked', () => {
		render( <AddMarket /> );

		expect(
			screen.queryByTestId( 'add-market-modal' )
		).not.toBeInTheDocument();
	} );

	test( 'opens AddMarketModal when the button is clicked', async () => {
		const user = userEvent.setup();
		render( <AddMarket /> );

		await user.click(
			screen.getByRole( 'button', { name: 'Add market' } )
		);

		expect( screen.getByTestId( 'add-market-modal' ) ).toBeInTheDocument();
	} );

	test( 'closes the modal when AddMarketModal calls onRequestClose', async () => {
		const user = userEvent.setup();
		render( <AddMarket /> );

		await user.click(
			screen.getByRole( 'button', { name: 'Add market' } )
		);
		expect( screen.getByTestId( 'add-market-modal' ) ).toBeInTheDocument();

		await user.click(
			screen.getByRole( 'button', { name: 'Close modal' } )
		);
		expect(
			screen.queryByTestId( 'add-market-modal' )
		).not.toBeInTheDocument();
	} );
} );
