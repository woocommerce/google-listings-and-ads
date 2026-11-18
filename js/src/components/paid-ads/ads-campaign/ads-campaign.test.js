/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AdsCampaign from './ads-campaign';

jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn().mockReturnValue( {} ),
} ) );
jest.mock( '../budget-section', () => ( props ) => <>{ props.children }</> );
jest.mock( '~/components/paid-ads/billing-card', () => () => null );
jest.mock( '../campaign-preview', () => ( {
	CampaignPreviewCard: () => null,
} ) );
jest.mock( './faqs', () => () => null );
jest.mock( './paid-ads-features-section', () => () => null );
jest.mock( '../eu-regulations-section', () => () => null );
jest.mock( './cyo-incentive-picker', () => ( props ) => (
	<div>CYO incentive picker for { props.context }</div>
) );

describe( 'AdsCampaign', () => {
	it.each( [ 'setup-mc', 'setup-ads-only', 'setup-ads', 'create-ads' ] )(
		'renders the incentive picker for the %s context',
		( context ) => {
			render( <AdsCampaign headerTitle="Title" context={ context } /> );

			expect(
				screen.getByText( `CYO incentive picker for ${ context }` )
			).toBeInTheDocument();
		}
	);

	it( 'does not render the incentive picker when editing a campaign', () => {
		render( <AdsCampaign headerTitle="Title" context="edit-ads" /> );

		expect(
			screen.queryByText( /CYO incentive picker/ )
		).not.toBeInTheDocument();
	} );
} );
