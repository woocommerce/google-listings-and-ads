/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

let mockGlaData;

jest.mock( '~/constants', () => ( {
	get glaData() {
		return mockGlaData;
	},
} ) );

/**
 * Loads a fresh copy of `PromoText`, since it reads `glaData.onboardingComplete` at module load.
 *
 * @param {boolean} onboardingComplete The onboarding state to load the component with.
 * @return {Function} The `PromoText` component.
 */
const loadPromoText = ( onboardingComplete ) => {
	mockGlaData = { onboardingComplete };

	let PromoText;
	jest.isolateModules( () => {
		PromoText = require( './promo-text' ).default;
	} );
	return PromoText;
};

describe( 'PromoText', () => {
	test( 'returns null when metricsCase is not recognized', () => {
		const PromoText = loadPromoText( false );
		const { container } = render( <PromoText metricsCase={ null } /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test.each( [
		[
			'revenue',
			false,
			'Sales a bit slow? Reach more shoppers with Google.',
			'Sync your catalog with Google and grow back your sales by reaching new shoppers right when they are searching to buy.',
		],
		[
			'revenue',
			true,
			'Sales a bit slow? Give your products a boost with Google.',
			'Launch a Google Ads campaign and grow back your sales by reaching shoppers who are ready to buy.',
		],
		[
			'products',
			false,
			'Selling fewer items than usual? Reach more shoppers with Google.',
			'Sync your catalog with Google and sell more of your products by reaching new shoppers right when they are searching to buy.',
		],
		[
			'products',
			true,
			'Selling fewer items than usual? Give your products a boost with Google.',
			'Launch a Google Ads campaign and sell more of your products by reaching shoppers who are ready to buy.',
		],
	] )(
		'%s × onboardingComplete=%s',
		( metricsCase, onboardingComplete, title, description ) => {
			const PromoText = loadPromoText( onboardingComplete );
			render( <PromoText metricsCase={ metricsCase } /> );

			expect(
				screen.getByRole( 'heading', { level: 3, name: title } )
			).toBeInTheDocument();
			expect( screen.getByText( description ) ).toBeInTheDocument();
		}
	);
} );
