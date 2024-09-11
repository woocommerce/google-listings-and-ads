/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import TermsModal from './';
import { FILTER_ONBOARDING } from '.~/utils/tracks';
import expectComponentToRecordEventWithFilteredProperties from '.~/tests/expectComponentToRecordEventWithFilteredProperties';

jest.mock( '@woocommerce/tracks', () => {
	return {
		recordEvent: jest.fn().mockName( 'recordEvent' ),
	};
} );

describe( 'TermsModal', () => {
	afterEach( () => {
		recordEvent.mockClear();
	} );

	it( 'should enable the "Create account" button after accepting the terms, and vice versa', async () => {
		const user = userEvent.setup();

		render( <TermsModal /> );

		const checkbox = screen.getByRole( 'checkbox' );
		const button = screen.getByRole( 'button', { name: 'Create account' } );

		expect( button ).toBeDisabled();

		await user.click( checkbox );

		expect( button ).toBeEnabled();

		await user.click( checkbox );

		expect( button ).toBeDisabled();
	} );

	it( 'should callback to onCreateAccount and onRequestClose when clicking on the "Create account" button', async () => {
		const user = userEvent.setup();
		const onCreateAccount = jest.fn();
		const onRequestClose = jest.fn();

		render(
			<TermsModal
				onCreateAccount={ onCreateAccount }
				onRequestClose={ onRequestClose }
			/>
		);

		expect( onCreateAccount ).toHaveBeenCalledTimes( 0 );
		expect( onRequestClose ).toHaveBeenCalledTimes( 0 );

		await user.click( screen.getByRole( 'checkbox' ) );
		await user.click(
			screen.getByRole( 'button', { name: 'Create account' } )
		);

		expect( onCreateAccount ).toHaveBeenCalledTimes( 1 );
		expect( onRequestClose ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should record click events for the "Create account" button and be aware of extra event properties from filters', async () => {
		const user = userEvent.setup();

		await expectComponentToRecordEventWithFilteredProperties(
			TermsModal,
			FILTER_ONBOARDING,
			async () => {
				const checkbox = screen.getByRole( 'checkbox' );
				if ( ! checkbox.checked ) {
					await user.click( screen.getByRole( 'checkbox' ) );
				}

				await user.click(
					screen.getByRole( 'button', { name: 'Create account' } )
				);
			},
			'gla_ads_account_create_button_click',
			[
				{ context: 'setup-mc', step: '1' },
				{ context: 'setup-ads', step: '2' },
			]
		);
	} );
} );
