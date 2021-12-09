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
	const onChange = jest.fn();

	const defaultProps = {
		formProps: {
			getInputProps: () => ( { onChange } ),
		},
	};

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
