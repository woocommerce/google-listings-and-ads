jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest
		.fn()
		.mockName( 'useAdaptiveFormContext' )
		.mockReturnValue( {
			getInputProps: () => ( {
				checked: false,
				onChange: jest.requireActual( 'lodash/noop' ),
			} ),
		} ),
} ) );
jest.mock( '~/hooks/useHasEuTargetCountry' );

/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useHasEuTargetCountry from '~/hooks/useHasEuTargetCountry';
import EuRegulationsCard from './eu-regulations-card';

describe( 'EuRegulationsCard', () => {
	test( 'Renders the title, text and EU political content field inside a card when an EU country is targeted', () => {
		useHasEuTargetCountry.mockReturnValue( {
			hasFinishedResolution: true,
			hasEuTargetCountry: true,
		} );

		const { container } = render(
			<EuRegulationsCard context="create-ads" />
		);

		const card = container.querySelector( '.components-card' );
		expect(
			card.querySelector( '.gla-section-card-body--padding-medium' )
		).toBeInTheDocument();
		const titles = card.querySelectorAll( '.gla-subsection-title' );
		expect( titles ).toHaveLength( 1 );
		expect( titles[ 0 ] ).toHaveTextContent( 'EU regulations' );
		expect( card ).toHaveTextContent(
			'Advertisers must confirm whether their ads include political content.'
		);
		expect( card ).toContainElement(
			screen.getByRole( 'checkbox', {
				name: /My ads include political content/,
			} )
		);
	} );

	test.each( [
		[ 'no target country is in the EU', true, false ],
		[ 'the data is still loading', false, false ],
	] )(
		'Renders nothing when %s',
		( _, hasFinishedResolution, hasEuTargetCountry ) => {
			useHasEuTargetCountry.mockReturnValue( {
				hasFinishedResolution,
				hasEuTargetCountry,
			} );

			const { container } = render(
				<EuRegulationsCard context="create-ads" />
			);

			expect( container ).toBeEmptyDOMElement();
		}
	);
} );
