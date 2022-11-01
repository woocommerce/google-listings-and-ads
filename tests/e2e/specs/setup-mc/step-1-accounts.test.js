/**
 * External dependencies
 */
import {
	merchant, // eslint-disable-line import/named
} from '@woocommerce/e2e-utils';
/**
 * Internal dependencies
 */
import { createURL } from '../../utils/create-url';
import { visitGLAPage } from '../../utils/visit-gla-page';
import { RequestMock } from '../../utils/request-mock';

const requestMock = new RequestMock();

describe( 'At setup page', () => {
	beforeAll( async () => {
		await merchant.login();
	} );
	afterAll( () => {
		requestMock.disconnect();
	} );

	afterEach( async function goToSetup() {
		requestMock.restore();
	} );

	describe( 'Merchant who is getting started', () => {
		beforeEach( async function goToSetup() {
			// Go to the setup page short way - directly via URL.
			await visitGLAPage( { path: '/google/setup-mc' } );
		} );

		it( 'should see accounts step header, "Connect your WordPress.com account" & connect button', async () => {
			// Wait for API calls and the page to render.
			await expect(
				page.waitForXPath( "//*[text()='Set up your accounts']" )
			).resolves.toBeTruthy();

			await expect(
				page.waitForXPath(
					"//*[text()='Connect the accounts required to use Google Listings & Ads.']"
				)
			).resolves.toBeTruthy();

			expect(
				page.$x( "//button[text()='Connect'][not(@disabled)]" )
			).toBeTruthy();
		} );

		describe( 'after clicking the "Connect your WordPress.com account" button', () => {
			let jetpackConnectMock;

			beforeEach( async function mockJetpackConnect() {
				await requestMock.observe( page );

				jetpackConnectMock = requestMock
					// Match "connect" not "connected".
					.mock( /\/wc\/gla\/jetpack\/connect\b/ )
					.mockImplementation( ( interceptedRequest ) => {
						// Mock connected
						interceptedRequest.respond( {
							content: 'application/json',
							headers: { 'Access-Control-Allow-Origin': '*' },
							body: JSON.stringify( {
								url: createURL( '', 'auth_url' ),
							} ),
						} );
						return true;
					} );
			} );

			it( 'should sent an API request to connect Jetpack, and redirect to the returned URL', async () => {
				// Wait for enabled button.
				const connectWPButton = await page.waitForXPath(
					"//button[text()='Connect'][not(@disabled)]"
				);

				// Click the button.
				await connectWPButton.click();

				// Wait for all XHRs and redirects.
				await page.waitForNavigation( { waitUntil: 'networkidle0' } );

				// Expect Jetpack call to be executed.
				expect( jetpackConnectMock ).toBeCalled();
				// Expect the user to be redirected
				expect( page.url() ).toEqual( createURL( '', 'auth_url' ) );
			} );
		} );
	} );
	describe( 'Merchant who has their Jetpack connected, but Google not yet connected', () => {
		beforeEach( async function goToSetup() {
			// Mock Jetpack as connected
			await requestMock.observe( page );
			requestMock
				.mock( /\/wc\/gla\/jetpack\/connected\b/ )
				.mockImplementation( ( interceptedRequest ) => {
					interceptedRequest.respond( {
						content: 'application/json',
						headers: { 'Access-Control-Allow-Origin': '*' },
						body: JSON.stringify( {
							active: 'yes',
							owner: 'yes',
							displayName: 'testUser',
							email: 'mail@example.com',
						} ),
					} );
					return true;
				} );

			// Mock google as not connected.
			// When pending even WPORG will not render yet.
			// If not mocked will fail and render nothing,
			// as Jetpack is mocked only on the client-side.
			requestMock
				.mock( /\/wc\/gla\/google\/connected\b/ )
				.mockImplementation( ( interceptedRequest ) => {
					interceptedRequest.respond( {
						content: 'application/json',
						headers: { 'Access-Control-Allow-Origin': '*' },
						body: JSON.stringify( {
							active: 'no',
							email: '',
						} ),
					} );

					return true;
				} );

			// Go to the setup page short way - directly via URL.
			await visitGLAPage( { path: '/google/setup-mc' } );
			// Wait for API calls and the page to render.
			await page.waitForXPath( "//*[text()='Set up your accounts']" );
		} );

		it( 'should see their WPORG email, "Google" title & connect button', async () => {
			await expect(
				page.waitForXPath( "//*[text()='mail@example.com']" )
			).resolves.toBeTruthy();

			await expect(
				page.waitForXPath( "//*[text()='Google']" )
			).resolves.toBeTruthy();

			await expect(
				page.$x( "//button[text()='Connect'][not(@disabled)]" )
			).resolves.toBeTruthy();
		} );

		describe( 'after clicking the "Connect your Google account" button', () => {
			let googleConnectMock;
			beforeEach( async function mockGoogleConnect() {
				// Spy on Google connection request.
				googleConnectMock = requestMock
					.mock( /\/wc\/gla\/google\/connect\b/ )
					.mockImplementation( ( interceptedRequest ) => {
						interceptedRequest.respond( {
							content: 'application/json',
							headers: { 'Access-Control-Allow-Origin': '*' },
							body: JSON.stringify( {
								url: createURL( '', 'google_auth' ),
							} ),
						} );
						return true;
					} );
			} );

			it( 'should sent an API request to connect Google account, and redirect to the returned URL', async () => {
				const connectGoogleButton = await page.waitForXPath(
					"//button[text()='Connect'][not(@disabled)]"
				);

				// Click the button
				await connectGoogleButton.click();

				// Wait for all XHRs and redirects.
				await page.waitForNavigation( { waitUntil: 'networkidle0' } );

				// Expect Google call to be executed.
				expect( googleConnectMock ).toBeCalled();
				// Expect the user to be redirected
				expect( page.url() ).toEqual( createURL( '', 'google_auth' ) );
			} );
		} );
	} );
} );
