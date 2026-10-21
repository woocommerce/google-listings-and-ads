/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useSettings from '~/hooks/useSettings';
import MultiLingualPluginPrompt from '.';

jest.mock( '~/hooks/useSettings' );

describe( 'MultiLingualPluginPrompt', () => {
	beforeEach( () => {
		global.glaData.isMultiLingualStore = false;

		useSettings.mockReturnValue( {
			settings: { shipping_rate: 'manual' },
		} );
	} );

	afterEach( () => {
		delete global.glaData.isMultiLingualStore;
	} );

	test( 'renders the plugin list when conditions are met', () => {
		render( <MultiLingualPluginPrompt /> );

		const prompt = document.querySelector(
			'.gla-multilingual-plugin-prompt'
		);
		expect( prompt ).toBeInTheDocument();
		expect( prompt ).toHaveTextContent( 'WPML' );
		expect( prompt ).toHaveTextContent(
			'WooCommerce integration that handles multi-currency natively.'
		);
		expect(
			screen.getByRole( 'link', { name: 'Learn more' } )
		).toBeInTheDocument();
	} );

	test( 'does not render when isMultiLingualStore is true', () => {
		global.glaData.isMultiLingualStore = true;

		render( <MultiLingualPluginPrompt /> );

		expect(
			document.querySelector( '.gla-multilingual-plugin-prompt' )
		).not.toBeInTheDocument();
	} );

	test( 'does not render when shipping_rate is not manual', () => {
		useSettings.mockReturnValue( {
			settings: { shipping_rate: 'flat' },
		} );

		render( <MultiLingualPluginPrompt /> );

		expect(
			document.querySelector( '.gla-multilingual-plugin-prompt' )
		).not.toBeInTheDocument();
	} );
} );
