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

	describe( 'Generate assets with GenAI button', () => {
		it( 'Card should have title "Review Your AI Suggestions"', () => {
			useGoogleAdsAccount.mockReturnValue( {} );
			render( <GenAICard /> );

			expect(
				screen.getByText( 'Review Your AI Suggestions' )
			).toBeInTheDocument();
		} );

		it( 'Card should have the expected description', () => {
			useGoogleAdsAccount.mockReturnValue( {} );
			render( <GenAICard /> );

			expect(
				screen.getByText(
					'Google AI analyzed your campaign’s URL to automatically generate your ad assets. Please review the suggested text and images below to ensure they align with your brand.'
				)
			).toBeInTheDocument();
		} );

		it( 'Match the snapshot', () => {
			useGoogleAdsAccount.mockReturnValue( {} );
			const { asFragment } = render( <GenAICard /> );
			expect( asFragment() ).toMatchSnapshot();
		} );
	} );
} );
