/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { screen, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import CampaignSetup from './campaign-setup';
import CampaignAssetsForm from './campaign-assets-form';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import useBudgetRecommendation from '~/hooks/useBudgetRecommendation';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

jest.mock( '~/hooks/useAdsCurrency', () =>
	jest.fn().mockName( 'useAdsCurrency' )
);

jest.mock( '~/hooks/useBudgetRecommendation', () =>
	jest.fn().mockName( 'useBudgetRecommendation' )
);

jest.mock( '~/hooks/useGoogleMCAccount', () =>
	jest.fn().mockName( 'useGoogleMCAccount' )
);

jest.mock( '~/hooks/useRaiseBudgetRecommendations', () =>
	jest.fn().mockReturnValue( {
		hasFinishedResolution: true,
	} )
);

const alwaysValid = () => ( {} );

function renderCampaignSetup( props ) {
	return render(
		<CampaignAssetsForm validate={ alwaysValid }>
			<CampaignSetup { ...props } />
		</CampaignAssetsForm>
	);
}

describe( 'CampaignSetup', () => {
	beforeEach( () => {
		useAdsCurrency.mockReturnValue( {
			formatAmount: jest.fn().mockName( 'formatAmount' ),
		} );

		useBudgetRecommendation.mockReturnValue( {
			hasResolved: true,
			data: {
				dailyBudgetBaseline: 13,
				recommendedDailyBudget: 15,
				recommended: { dailyBudget: 15 },
				high: { dailyBudget: 30 },
				low: { dailyBudget: 5 },
			},
		} );

		useGoogleMCAccount.mockReturnValue( {
			hasFinishedResolution: true,
			hasGoogleMCConnection: true,
		} );
	} );

	it( 'Should render its children', () => {
		renderCampaignSetup( {
			children: <div>First section</div>,
		} );

		expect( screen.getByText( 'First section' ) ).toBeInTheDocument();
	} );

	it( 'Should render the continue button when it is an element', () => {
		renderCampaignSetup( {
			continueButton: <button>Continue element</button>,
		} );

		expect(
			screen.getByRole( 'button', { name: 'Continue element' } )
		).toBeInTheDocument();
	} );

	it( 'Should render the continue button returned by a function that receives the form context', () => {
		const continueButton = jest.fn( () => (
			<button>Continue function</button>
		) );

		renderCampaignSetup( { continueButton } );

		expect(
			screen.getByRole( 'button', { name: 'Continue function' } )
		).toBeInTheDocument();
		expect( continueButton ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				values: expect.any( Object ),
				adapter: expect.any( Object ),
				handleSubmit: expect.any( Function ),
			} )
		);
	} );

	it( 'Should render the sections, then the continue button, then the FAQs', () => {
		const { container } = renderCampaignSetup( {
			children: <div>First section</div>,
			continueButton: <button>Continue</button>,
		} );

		const { textContent } = container;
		const sectionIndex = textContent.indexOf( 'First section' );
		const buttonIndex = textContent.indexOf( 'Continue' );
		const faqIndex = textContent.indexOf( 'What will my ads look like?' );

		expect( sectionIndex ).toBeGreaterThanOrEqual( 0 );
		expect( buttonIndex ).toBeGreaterThan( sectionIndex );
		expect( faqIndex ).toBeGreaterThan( buttonIndex );
	} );

	it( 'Should render the asset group FAQs', () => {
		renderCampaignSetup();

		expect(
			screen.getByText( 'What will my ads look like?' )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'What makes these ads different from product ads?'
			)
		).toBeInTheDocument();
	} );

	it( 'Should not render a step title above the sections', () => {
		renderCampaignSetup( { children: <div>First section</div> } );

		const [ firstHeading ] = screen.getAllByRole( 'heading' );
		const section = screen.getByText( 'First section' );

		expect( firstHeading ).toHaveTextContent(
			'Frequently asked questions'
		);
		expect( section.compareDocumentPosition( firstHeading ) ).toBe(
			section.DOCUMENT_POSITION_FOLLOWING
		);
	} );
} );
