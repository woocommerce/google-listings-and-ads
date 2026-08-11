/**
 * Internal dependencies
 */
import { BANNER_CLASS, CONTAINER_CLASS, MULTICHANNEL_CLASS } from './constants';

const observerCallbacks = [];
const mockRender = jest.fn();
const mockUnmount = jest.fn();

jest.mock( '@wordpress/element', () => ( {
	createRoot: jest.fn( () => ( {
		render: ( ...args ) => mockRender( ...args ),
		unmount: ( ...args ) => mockUnmount( ...args ),
	} ) ),
} ) );

jest.mock( './data', () => ( {
	registerStore: jest.fn(),
} ) );

jest.mock( './components/notifications-panel', () => () => null );

jest.mock( 'lodash/debounce', () => jest.fn( ( fn ) => fn ) );

global.MutationObserver = jest.fn().mockImplementation( ( callback ) => {
	observerCallbacks.push( callback );

	return {
		observe: jest.fn(),
		disconnect: jest.fn(),
	};
} );

function createMultichannel() {
	const multichannel = document.createElement( 'div' );
	multichannel.className = MULTICHANNEL_CLASS;
	document.body.appendChild( multichannel );
	return multichannel;
}

function createBanner( multichannel ) {
	const banner = document.createElement( 'div' );
	banner.className = BANNER_CLASS;
	multichannel.appendChild( banner );
	return banner;
}

function flushObserverSync() {
	observerCallbacks.forEach( ( callback ) => callback() );
}

describe( 'initMarketingNotificationsSlot', () => {
	let init;
	let registerStore;
	let createRoot;

	beforeEach( async () => {
		document.body.innerHTML = '';
		observerCallbacks.length = 0;
		jest.resetModules();
		jest.clearAllMocks();

		( { registerStore } = await import( './data' ) );
		( { createRoot } = await import( '@wordpress/element' ) );
		init = ( await import( './slot' ) ).default;
	} );

	it( 'registers the store and mounts the slot when the multichannel section exists', () => {
		const multichannel = createMultichannel();
		createBanner( multichannel );

		init();
		flushObserverSync();

		expect( registerStore ).toHaveBeenCalledTimes( 1 );
		expect( createRoot ).toHaveBeenCalledTimes( 1 );

		const container = multichannel.querySelector( `.${ CONTAINER_CLASS }` );
		expect( container ).not.toBeNull();
		expect( container.previousElementSibling.className ).toBe(
			BANNER_CLASS
		);
		expect( mockRender ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'repositions the slot when React re-inserts the banner', () => {
		const multichannel = createMultichannel();
		const banner = createBanner( multichannel );

		init();
		flushObserverSync();

		const container = multichannel.querySelector( `.${ CONTAINER_CLASS }` );
		expect( container.previousElementSibling ).toBe( banner );

		banner.remove();
		flushObserverSync();

		const replacementBanner = createBanner( multichannel );
		flushObserverSync();

		expect(
			multichannel.querySelectorAll( `.${ CONTAINER_CLASS }` )
		).toHaveLength( 1 );
		expect( container.previousElementSibling ).toBe( replacementBanner );
		expect( createRoot ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'remounts the slot when React replaces the multichannel section', () => {
		const multichannel = createMultichannel();
		createBanner( multichannel );

		init();
		flushObserverSync();

		expect( createRoot ).toHaveBeenCalledTimes( 1 );

		multichannel.remove();
		flushObserverSync();

		const replacementMultichannel = createMultichannel();
		createBanner( replacementMultichannel );
		flushObserverSync();

		expect( createRoot ).toHaveBeenCalledTimes( 2 );
		expect(
			replacementMultichannel.querySelector( `.${ CONTAINER_CLASS }` )
		).not.toBeNull();
		expect(
			document.querySelectorAll( `.${ CONTAINER_CLASS }` )
		).toHaveLength( 1 );
		expect( mockUnmount ).toHaveBeenCalledTimes( 1 );
	} );
} );
