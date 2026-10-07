/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';

const FIELD_ID = 'gla_channel_visibility';

// Force `react` to always resolve to Jest's single real module instance, so
// that re-importing the component (and its `@wordpress/components` /
// `@wordpress/element` dependencies) via `jest.isolateModules` (to reset its
// module-level `glaData` destructuring) doesn't produce a second React copy
// and trigger "Invalid hook call" errors.
jest.mock( 'react', () => jest.requireActual( 'react' ) );

let mockGlaData;

jest.mock( '~/constants', () => ( {
	get glaData() {
		return mockGlaData;
	},
} ) );

/**
 * `channel_visibility` is destructured from `glaData` at module load time,
 * so the module must be re-required after `mockGlaData` changes.
 */
const loadComponent = () => {
	let Component;
	jest.isolateModules( () => {
		Component = require( './channel-visibility-settings' ).default;
	} );
	return Component;
};

describe( 'ChannelVisibilitySettings', () => {
	test( 'Toggle is checked by default when channel_visibility is sync-and-show', () => {
		mockGlaData = {
			channelVisibility: {
				field_id: FIELD_ID,
				channel_visibility: 'sync-and-show',
				product_is_visible: true,
			},
		};
		const ChannelVisibilitySettings = loadComponent();

		render( <ChannelVisibilitySettings /> );

		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
	} );

	test( 'Toggle is unchecked by default when channel_visibility is dont-sync-and-show', () => {
		mockGlaData = {
			channelVisibility: {
				field_id: FIELD_ID,
				channel_visibility: 'dont-sync-and-show',
				product_is_visible: true,
			},
		};
		const ChannelVisibilitySettings = loadComponent();

		render( <ChannelVisibilitySettings /> );

		expect( screen.getByRole( 'checkbox' ) ).not.toBeChecked();
	} );

	test( 'Clicking the toggle switches the checked state', () => {
		mockGlaData = {
			channelVisibility: {
				field_id: FIELD_ID,
				channel_visibility: 'sync-and-show',
				product_is_visible: true,
			},
		};
		const ChannelVisibilitySettings = loadComponent();

		render( <ChannelVisibilitySettings /> );

		const toggle = screen.getByRole( 'checkbox' );
		expect( toggle ).toBeChecked();

		fireEvent.click( toggle );
		expect( toggle ).not.toBeChecked();

		fireEvent.click( toggle );
		expect( toggle ).toBeChecked();
	} );

	test( 'Toggle is unchecked by default when channel_visibility is unset (new product)', () => {
		mockGlaData = {
			channelVisibility: {
				field_id: FIELD_ID,
				channel_visibility: null,
				product_is_visible: true,
			},
		};
		const ChannelVisibilitySettings = loadComponent();

		render( <ChannelVisibilitySettings /> );

		expect( screen.getByRole( 'checkbox' ) ).not.toBeChecked();
	} );

	test( 'Toggle is disabled and unchecked when the product is not visible', () => {
		mockGlaData = {
			channelVisibility: {
				field_id: FIELD_ID,
				channel_visibility: 'sync-and-show',
				product_is_visible: false,
			},
		};
		const ChannelVisibilitySettings = loadComponent();

		render( <ChannelVisibilitySettings /> );

		const toggle = screen.getByRole( 'checkbox' );
		expect( toggle ).toBeDisabled();
		expect( toggle ).not.toBeChecked();
	} );
} );
