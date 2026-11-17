jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn().mockName( 'useAdaptiveFormContext' ),
} ) );
jest.mock( './useHasEuTargetCountry' );

/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useHasEuTargetCountry from './useHasEuTargetCountry';
import EuRegulationsCard from './eu-regulations-card';

const CHECKBOX_NAME = /My ads include political content/;

describe( 'EuRegulationsCard', () => {
	beforeEach( () => {
		useAdaptiveFormContext.mockImplementation( () => {
			const [ checked, setChecked ] = useState( false );

			return {
				getInputProps: () => ( { checked, onChange: setChecked } ),
			};
		} );
	} );

	test( 'Renders the title, text, checkbox and help text inside a card when an EU country is targeted', () => {
		useHasEuTargetCountry.mockReturnValue( {
			hasFinishedResolution: true,
			hasEuTargetCountry: true,
		} );

		const { container } = render(
			<EuRegulationsCard context="create-ads" />
		);

		const card = container.querySelector( '.gla-eu-regulations-card' );
		const titles = card.querySelectorAll( '.gla-section-card-title' );
		expect( card ).toHaveClass( 'components-card' );
		expect( titles ).toHaveLength( 1 );
		expect( titles[ 0 ] ).toHaveTextContent( 'EU regulations' );
		expect( card ).toHaveTextContent(
			'Advertisers must confirm whether their ads include political content.'
		);
		expect(
			screen.getByRole( 'checkbox', { name: CHECKBOX_NAME } )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				"If selected, your ads will not run in the EU unless you complete Google's political advertiser verification."
			)
		).toBeInTheDocument();
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

	test( 'Shows the error when the checkbox is ticked', async () => {
		useHasEuTargetCountry.mockReturnValue( {
			hasFinishedResolution: true,
			hasEuTargetCountry: true,
		} );

		const { container } = render(
			<EuRegulationsCard context="create-ads" />
		);

		await userEvent.click(
			screen.getByRole( 'checkbox', { name: CHECKBOX_NAME } )
		);

		expect(
			container.querySelector( '.components-notice' )
		).toHaveTextContent( 'Your ads will not run in the EU' );
	} );
} );
