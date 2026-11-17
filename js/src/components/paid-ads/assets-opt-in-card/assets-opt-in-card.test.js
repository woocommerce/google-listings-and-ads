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
import AssetsOptInCard from './assets-opt-in-card';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import { ADVERTISING_SOURCE } from '~/constants';

jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn(),
} ) );

const NOTICE_TEXT =
	'If you also sell services, your ads in this campaign will mainly show your products rather than your services. Google recommends promoting your services in a separate campaign.';

// `Notice` also announces its text in a screen reader live region, so look inside the notice itself.
const getNotice = () => document.querySelector( '.components-notice' );

/**
 * Renders the card with a minimal stateful stand-in for the adaptive form context.
 *
 * @param {string} advertisingSource The advertising source in the form values.
 * @param {jest.Mock} onValueChange Called with the new `includeCreativeAssets` value.
 */
function renderCard( advertisingSource, onValueChange = jest.fn() ) {
	function Wrapper() {
		const [ includeCreativeAssets, setIncludeCreativeAssets ] =
			useState( false );

		useAdaptiveFormContext.mockReturnValue( {
			values: { advertisingSource, includeCreativeAssets },
			getInputProps: () => ( {
				checked: includeCreativeAssets,
				onChange: ( value ) => {
					onValueChange( value );
					setIncludeCreativeAssets( value );
				},
			} ),
		} );

		return <AssetsOptInCard />;
	}

	return render( <Wrapper /> );
}

describe( 'AssetsOptInCard', () => {
	it( 'Shows the label, "(Optional)" and help text for products', () => {
		renderCard( ADVERTISING_SOURCE.PRODUCT );

		expect(
			screen.getByRole( 'checkbox', {
				name: /Add text, images, and videos/,
			} )
		).toBeInTheDocument();
		expect( screen.getByText( '(Optional)' ) ).toBeInTheDocument();
		expect(
			screen.getByText(
				'Add your own headlines, descriptions, images, and videos alongside your products. Google AI suggests some to start with.'
			)
		).toBeInTheDocument();
	} );

	it( 'Renders nothing for a page', () => {
		const { container } = renderCard( ADVERTISING_SOURCE.PAGE );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'Starts unticked with no notice', () => {
		renderCard( ADVERTISING_SOURCE.PRODUCT );

		expect( screen.getByRole( 'checkbox' ) ).not.toBeChecked();
		expect( getNotice() ).not.toBeInTheDocument();
	} );

	it( 'Sets the form value and shows the notice when ticked', async () => {
		const onValueChange = jest.fn();
		renderCard( ADVERTISING_SOURCE.PRODUCT, onValueChange );

		await userEvent.click( screen.getByRole( 'checkbox' ) );

		expect( onValueChange ).toHaveBeenCalledWith( true );
		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
		expect( getNotice() ).toHaveTextContent( NOTICE_TEXT );
		expect( getNotice() ).toHaveClass( 'is-info' );
	} );

	it( 'Hides the notice when unticked', async () => {
		renderCard( ADVERTISING_SOURCE.PRODUCT );

		await userEvent.click( screen.getByRole( 'checkbox' ) );
		await userEvent.click( screen.getByRole( 'checkbox' ) );

		expect( getNotice() ).not.toBeInTheDocument();
	} );
} );
