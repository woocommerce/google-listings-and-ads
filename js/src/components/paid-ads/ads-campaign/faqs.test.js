/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import Faqs from './faqs';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
);

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

describe( 'AdsCampaign Faqs', () => {
	it( 'should show the services answer without the raw placeholder when there is no Merchant Center', async () => {
		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: false } );
		render( <Faqs /> );

		await userEvent.click(
			screen.getByRole( 'button', {
				name: "I don't have physical products. Can I still use this?",
			} )
		);

		expect(
			screen.getByText(
				'Yes! It’s designed specifically for service providers. It treats your "Services" as the offer and optimizes for leads or appointments.'
			)
		).toBeInTheDocument();
		expect(
			screen.queryByText( /\{give_examples\}/ )
		).not.toBeInTheDocument();
	} );

	it( 'should show the product FAQs when there is a Merchant Center', () => {
		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: true } );
		render( <Faqs /> );

		const questions = screen
			.getAllByRole( 'button' )
			.map( ( button ) => button.textContent );

		expect( questions ).toEqual( [
			'How does Google Ads work?',
			'What is a product feed?',
			'How much does Google Ads cost?',
			'Where will my products appear?',
			'How long until I see results with Google Ads?',
		] );
	} );
} );
