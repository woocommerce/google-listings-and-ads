/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import AdvertisingSourceSelector from './advertising-source-selector';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import { ADVERTISING_SOURCE } from '~/constants';

jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn(),
} ) );
jest.mock( '~/hooks/useGoogleMCAccount', () => jest.fn() );

describe( 'AdvertisingSourceSelector', () => {
	let onChange;

	beforeEach( () => {
		onChange = jest.fn();
		useAdaptiveFormContext.mockReturnValue( {
			getInputProps: () => ( {
				value: ADVERTISING_SOURCE.PRODUCT,
				onChange,
			} ),
		} );
		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: true } );
	} );

	it( 'Shows the title, description and both choices with their text', () => {
		render( <AdvertisingSourceSelector /> );

		expect(
			screen.getByText( 'What do you want to advertise?' )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Your products synced to Merchant Center, or a page from your store.'
			)
		).toBeInTheDocument();
		expect( screen.getByLabelText( 'My products' ) ).toBeInTheDocument();
		expect(
			screen.getByText(
				"This campaign will use information from the product feed you've synced with Google Merchant Center."
			)
		).toBeInTheDocument();
		expect(
			screen.getByLabelText( 'Another page on my store' )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Advertise a different page on your store, such as a service booking page or your homepage.'
			)
		).toBeInTheDocument();
	} );

	it( 'Selects "My products" by default', () => {
		render( <AdvertisingSourceSelector /> );

		expect( screen.getByLabelText( 'My products' ) ).toBeChecked();
		expect(
			screen.getByLabelText( 'Another page on my store' )
		).not.toBeChecked();
	} );

	it( 'Sets the form value to page when selecting "Another page on my store"', async () => {
		render( <AdvertisingSourceSelector /> );

		await userEvent.click(
			screen.getByLabelText( 'Another page on my store' )
		);

		expect( onChange ).toHaveBeenCalledWith( ADVERTISING_SOURCE.PAGE );
	} );

	it( 'Renders nothing without a Merchant Center connection', () => {
		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: false } );

		const { container } = render( <AdvertisingSourceSelector /> );

		expect( container ).toBeEmptyDOMElement();
	} );
} );
