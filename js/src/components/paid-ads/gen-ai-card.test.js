/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import GenAICard from './gen-ai-card';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';

// Mock useGoogleAdsAccount
jest.mock( '~/hooks/useGoogleAdsAccount', () =>
	jest.fn().mockName( 'useGoogleAdsAccount' )
);

describe( 'GenAICard', () => {
	afterEach( () => {
		jest.clearAllMocks();
	} );

	const getGenAIButton = () =>
		screen.queryByRole( 'button', {
			name: /Generate Assets with GenAI/i,
		} ) ||
		screen.queryByRole( 'link', { name: /Generate Assets with GenAI/i } );

	describe( 'Generate assets with GenAI button', () => {
		it( 'disables the button if googleAdsAccount is missing', () => {
			useGoogleAdsAccount.mockReturnValue( {} );
			render( <GenAICard /> );
			expect( getGenAIButton() ).toBeDisabled();
		} );

		it( 'disables the button if googleAdsAccount.status is not "connected"', () => {
			useGoogleAdsAccount.mockReturnValue( {
				googleAdsAccount: { id: '123', ocid: '456', status: 'pending' },
			} );
			render( <GenAICard /> );
			expect( getGenAIButton() ).toBeDisabled();
		} );

		it( 'enables the button if googleAdsAccount.status is "connected"', () => {
			useGoogleAdsAccount.mockReturnValue( {
				googleAdsAccount: {
					id: '123',
					ocid: '456',
					status: 'connected',
				},
			} );
			render( <GenAICard /> );
			expect( getGenAIButton() ).not.toBeDisabled();
		} );

		it( 'generates the correct recommendations URL when both ecid and ocid are available', () => {
			useGoogleAdsAccount.mockReturnValue( {
				googleAdsAccount: {
					id: '123',
					ocid: '456',
					status: 'connected',
				},
			} );
			render( <GenAICard /> );
			const button = getGenAIButton();
			expect( button ).toHaveAttribute(
				'href',
				expect.stringContaining( 'ocid=456' )
			);
			expect( button ).not.toHaveAttribute(
				'href',
				expect.stringContaining( 'ecid=' )
			);
		} );

		it( 'generates the correct recommendations URL with only ecid', () => {
			useGoogleAdsAccount.mockReturnValue( {
				googleAdsAccount: { id: '123', status: 'connected' },
			} );
			render( <GenAICard /> );
			const button = getGenAIButton();
			expect( button ).toHaveAttribute(
				'href',
				expect.stringContaining( 'ecid=123' )
			);
			expect( button ).not.toHaveAttribute(
				'href',
				expect.stringContaining( 'ocid=' )
			);
		} );
	} );
} );
