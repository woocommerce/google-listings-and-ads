jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn().mockName( 'useAdaptiveFormContext' ),
} ) );

/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import EuPoliticalContentField from './eu-political-content-field';

const CHECKBOX_NAME = /My ads include political content/;
const ERROR_TEXT = 'Your ads will not run in the EU';

describe( 'EuPoliticalContentField', () => {
	beforeEach( () => {
		useAdaptiveFormContext.mockImplementation( () => {
			const [ checked, setChecked ] = useState( false );

			return {
				getInputProps: () => ( { checked, onChange: setChecked } ),
			};
		} );
	} );

	test( 'Renders the checkbox and its help text without the error', () => {
		const { container } = render(
			<EuPoliticalContentField context="create-ads" />
		);

		expect(
			screen.getByRole( 'checkbox', { name: CHECKBOX_NAME } )
		).not.toBeChecked();
		expect(
			screen.getByText(
				"If selected, your ads will not run in the EU unless you complete Google's political advertiser verification."
			)
		).toBeInTheDocument();
		expect(
			container.querySelector( '.components-notice' )
		).not.toBeInTheDocument();
	} );

	test( 'Shows the error when the checkbox is ticked', async () => {
		const { container } = render(
			<EuPoliticalContentField context="create-ads" />
		);

		await userEvent.click(
			screen.getByRole( 'checkbox', { name: CHECKBOX_NAME } )
		);

		expect(
			container.querySelector( '.components-notice' )
		).toHaveTextContent( ERROR_TEXT );
	} );
} );
