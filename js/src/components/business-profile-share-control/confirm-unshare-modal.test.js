/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConfirmUnshareModal from './confirm-unshare-modal';

describe( 'ConfirmUnshareModal', () => {
	it( 'explains that the post stays published while the update is removed', () => {
		render(
			<ConfirmUnshareModal
				onConfirm={ jest.fn() }
				onRequestClose={ jest.fn() }
			/>
		);

		expect(
			screen.getByRole( 'dialog', { name: 'Turn off sharing?' } )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'This removes the update from your Business Profile. The post stays published on your store.'
			)
		).toBeInTheDocument();
	} );

	it( 'confirms on "Turn off sharing"', async () => {
		const user = userEvent.setup();
		const onConfirm = jest.fn();
		render(
			<ConfirmUnshareModal
				onConfirm={ onConfirm }
				onRequestClose={ jest.fn() }
			/>
		);

		await user.click(
			screen.getByRole( 'button', { name: 'Turn off sharing' } )
		);

		expect( onConfirm ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'closes without confirming on "Cancel"', async () => {
		const user = userEvent.setup();
		const onConfirm = jest.fn();
		const onRequestClose = jest.fn();
		render(
			<ConfirmUnshareModal
				onConfirm={ onConfirm }
				onRequestClose={ onRequestClose }
			/>
		);

		await user.click( screen.getByRole( 'button', { name: 'Cancel' } ) );

		expect( onRequestClose ).toHaveBeenCalledTimes( 1 );
		expect( onConfirm ).not.toHaveBeenCalled();
	} );
} );
