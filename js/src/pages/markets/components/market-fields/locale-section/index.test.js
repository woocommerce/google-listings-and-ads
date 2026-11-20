/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useSettings from '~/hooks/useSettings';
import LocaleSection from '.';

jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn().mockImplementation( () => ( {
		adapter: { isPrimaryMarket: false },
	} ) ),
} ) );

jest.mock( '~/hooks/useSettings' );

jest.mock( './language-select-control', () =>
	jest.fn( () => <div data-testid="language-select-control" /> )
);

jest.mock( './currency-select-control', () =>
	jest.fn( () => <div data-testid="currency-select-control" /> )
);

describe( 'LocaleSection', () => {
	beforeEach( () => {
		useSettings.mockReturnValue( {
			settings: { shipping_rate: 'manual' },
		} );
		useAdaptiveFormContext.mockImplementation( () => ( {
			adapter: { isPrimaryMarket: false },
		} ) );
		window.glaData.isMultiLingualStore = false;
	} );

	afterEach( () => {
		useSettings.mockReset();
		delete window.glaData.isMultiLingualStore;
	} );

	test( 'renders null when shipping_rate is flat', () => {
		useSettings.mockReturnValue( {
			settings: { shipping_rate: 'flat' },
		} );

		const { container } = render( <LocaleSection /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders null when shipping_rate is flat on a multilingual store', () => {
		// Flat shipping is incompatible with multilingual feeds, so the
		// controls stay hidden even when a multilingual plugin is active.
		window.glaData.isMultiLingualStore = true;
		useSettings.mockReturnValue( {
			settings: { shipping_rate: 'flat' },
		} );

		const { container } = render( <LocaleSection /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders null for the primary market on a non-multilingual store', () => {
		useAdaptiveFormContext.mockImplementation( () => ( {
			adapter: { isPrimaryMarket: true },
		} ) );

		const { container } = render( <LocaleSection /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	test( 'renders locale controls for a non-primary market on a non-multilingual store', () => {
		render( <LocaleSection /> );

		expect(
			screen.getByTestId( 'language-select-control' )
		).toBeInTheDocument();
		expect(
			screen.getByTestId( 'currency-select-control' )
		).toBeInTheDocument();
	} );

	test( 'renders locale controls for the primary market on a multilingual store', () => {
		window.glaData.isMultiLingualStore = true;
		useAdaptiveFormContext.mockImplementation( () => ( {
			adapter: { isPrimaryMarket: true },
		} ) );

		render( <LocaleSection /> );

		expect(
			screen.getByTestId( 'language-select-control' )
		).toBeInTheDocument();
		expect(
			screen.getByTestId( 'currency-select-control' )
		).toBeInTheDocument();
	} );

	test( 'shows the multilingual plugin notice on a non-multilingual store', () => {
		const { container } = render( <LocaleSection /> );

		const notice = container.querySelector( '.components-notice' );
		expect( notice ).toBeInTheDocument();
		expect( notice ).toHaveTextContent(
			'Want to sell in multiple languages?'
		);
	} );

	test( 'does not show the multilingual plugin notice on a multilingual store', () => {
		window.glaData.isMultiLingualStore = true;

		const { container } = render( <LocaleSection /> );

		expect( container.querySelector( '.components-notice' ) ).toBeNull();
	} );
} );
