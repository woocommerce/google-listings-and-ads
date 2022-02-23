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

describe( 'AudienceSection with multiple countries selector', () => {
	let defaultProps;
	let onChange;

	beforeEach( () => {
		onChange = jest.fn();
		defaultProps = {
			formProps: {
				getInputProps: () => ( { onChange } ),
			},
		};
	} );

	test( 'If Audience section is disabled the country field should be disabled', async () => {
		render( <AudienceSection { ...defaultProps } disabled={ true } /> );

		const dropdown = await screen.findByRole( 'combobox' );
		expect( dropdown ).toBeDisabled();

		//Test that input is not editable
		userEvent.clear( dropdown );
		userEvent.type( dropdown, 'S' );

		const options = screen.queryAllByRole( 'option' );
		expect( options.length ).toBe( 0 );
		expect( onChange ).toHaveBeenCalledTimes( 0 );
	} );

	test( 'If Audience section is enable the country field should be enable & editable', async () => {
		render( <AudienceSection { ...defaultProps } disabled={ false } /> );

		const dropdown = await screen.findByRole( 'combobox' );
		expect( dropdown ).not.toBeDisabled();

		//Test that input is editable
		userEvent.clear( dropdown );
		userEvent.type( dropdown, 'S' );

		const options = await screen.findAllByRole( 'option' );
		expect( options.length ).toBeGreaterThan( 0 );

		const firstOption = options[ 0 ];
		userEvent.click( firstOption );
		expect( onChange ).toHaveBeenCalledTimes( 1 );
	} );
} );

describe( 'AudienceSection with single country selector', () => {
	let defaultProps;
	let onChange;

	beforeEach( () => {
		onChange = jest.fn();
		defaultProps = {
			multiple: false,
			formProps: {
				getInputProps: () => ( {
					value: [ 'US', 'ES', 'GB' ],
					selected: [ 'ES' ],
					onChange,
				} ),
			},
		};
	} );

	test( 'When AudienceSection is disabled, the country field should be disabled', () => {
		render( <AudienceSection { ...defaultProps } disabled /> );
		const dropdown = screen.queryByRole( 'combobox' );

		expect( dropdown ).toBeDisabled();
	} );

	test( 'When AudienceSection is enable, the country field should be enable', () => {
		render( <AudienceSection { ...defaultProps } /> );
		const dropdown = screen.queryByRole( 'combobox' );

		expect( dropdown ).not.toBeDisabled();
	} );

	test( 'When selecting another option, the country field should trigger `onChange` callback', () => {
		render( <AudienceSection { ...defaultProps } /> );

		const dropdown = screen.queryByRole( 'combobox' );
		userEvent.selectOptions( dropdown, 'GB' );

		expect( onChange ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'The country field should have the given options', () => {
		render( <AudienceSection { ...defaultProps } /> );
		const options = screen.queryAllByRole( 'option' );

		expect( options.length ).toBe( 3 );
	} );

	test( 'The country field should select the option by given value', () => {
		render( <AudienceSection { ...defaultProps } /> );
		const option = screen.queryByRole( 'option', { selected: true } );

		expect( option.value ).toBe( 'ES' );
	} );
} );
