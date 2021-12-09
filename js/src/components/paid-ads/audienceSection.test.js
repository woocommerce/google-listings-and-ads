/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import AudienceSection from '.~/components/paid-ads/audience-section';

jest.mock( '.~/hooks/useCountryKeyNameMap', () =>
	jest.fn( () => ( {
		GB: 'United Kingdom',
		US: 'United States',
		ES: 'Spain',
	} ) )
);

jest.mock( '.~/hooks/useTargetAudienceFinalCountryCodes', () =>
	jest.fn( () => ( { data: [ 'GB', 'US', 'ES' ] } ) )
);

describe( 'audienceSection', () => {
	const defaultProps = {
		formProps: {
			getInputProps: () => ( { value: [ 'GB' ] } ),
		},
		countrySelectHelperText:
			'Once a campaign has been created, you cannot change the target country.',
	};

	test( 'If Audience section is disabled the country field should be disabled', async () => {
		render( <AudienceSection { ...defaultProps } disabled={ true } /> );

		const dropdown = await screen.findByRole( 'combobox' );
		expect( dropdown ).toBeDisabled();

		//Test that input is not editable
		userEvent.type( dropdown, 'Spain' );
		expect( dropdown ).toHaveValue( 'United Kingdom' );
	} );

	test( 'If Audience section is enable the country field should be enable', async () => {
		render( <AudienceSection { ...defaultProps } disabled={ false } /> );

		const dropdown = await screen.findByRole( 'combobox' );
		expect( dropdown ).not.toBeDisabled();

		//Test that input is editable
		userEvent.clear( dropdown );
		userEvent.type( dropdown, 'S' );
		expect( dropdown ).toHaveValue( 'S' );
	} );
} );
