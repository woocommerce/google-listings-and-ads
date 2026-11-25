/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';
import { Editor } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import {
	clearBusinessProfileLocation,
	getBusinessProfileShareState,
	setBusinessProfileLocation,
} from '../../utils/api';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

const SHARE_META_KEY = '_wc_gla_gbp_share';
const SUMMARY_META_KEY = '_wc_gla_gbp_summary';
const SUMMARY_MAX_LENGTH = 1500;
const SUMMARY = 'Fresh sourdough every morning. Come by before nine.';

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

/**
 * @type {Editor} editor
 */
let editor = null;

function getPanel() {
	return page
		.getByRole( 'region', { name: 'Editor settings' } )
		.locator( '.components-panel__body' )
		.filter( {
			has: page.getByRole( 'button', {
				name: 'Google Business Profile',
			} ),
		} );
}

/**
 * Opens a new post in the block editor, with the post settings sidebar and the Google Business
 * Profile panel open.
 */
async function gotoNewPost() {
	await page.goto( '/wp-admin/post-new.php' );
	await editor.setPreferences( 'core/edit-post', {
		welcomeGuide: false,
		fullscreenMode: false,
	} );
	await page.reload();
	await editor.openDocumentSettingsSidebar();

	const panelButton = getPanel().getByRole( 'button', {
		name: 'Google Business Profile',
	} );

	await expect( panelButton ).toBeVisible();

	if ( ( await panelButton.getAttribute( 'aria-expanded' ) ) === 'false' ) {
		await panelButton.click();
	}
}

function getShareToggle() {
	return getPanel().getByRole( 'checkbox', {
		name: 'Share on my Business Profile',
	} );
}

function getSummary() {
	return getPanel().getByRole( 'textbox', { name: 'Summary' } );
}

function getSummaryCount() {
	return getPanel().getByText( /\d+\/\d+ characters/ );
}

async function fillTitle( title = 'Fresh from the oven' ) {
	await editor.canvas
		.getByRole( 'textbox', { name: 'Add title' } )
		.fill( title );
}

/**
 * Publishes the post through the editor, and resolves with the request that saved it as published.
 *
 * @return {Promise<import('@playwright/test').Request>} The request saving the post.
 */
async function publishAndGetSaveRequest() {
	const requestPromise = page.waitForRequest( ( request ) => {
		const isPostSave =
			/\/wp\/v2\/posts\/\d+/.test(
				decodeURIComponent( request.url() )
			) && [ 'POST', 'PUT', 'PATCH' ].includes( request.method() );

		return isPostSave && request.postDataJSON()?.status === 'publish';
	} );

	await editor.publishPost();

	return requestPromise;
}

// Not covered: the send to Google itself, which runs later as a background job, and whether the
// connected location can receive posts. The site cannot reach Google while the location is set.
test.describe( 'Google Business Profile publish toggle in the block editor', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		editor = new Editor( { page } );

		await setBusinessProfileLocation();
	} );

	test.afterAll( async () => {
		await clearBusinessProfileLocation();
		await page.close();
	} );

	test( 'should show the toggle off, without the Summary field, on a new post', async () => {
		await gotoNewPost();

		await expect( getShareToggle() ).not.toBeChecked();
		await expect(
			getPanel().getByText(
				"Share to Jane's Bakery as an update when you publish."
			)
		).toBeVisible();
		await expect( getSummary() ).toBeHidden();
	} );

	test( 'should show the Summary field with its character count once the toggle is on', async () => {
		await gotoNewPost();

		await getShareToggle().check();

		await expect( getSummary() ).toBeVisible();
		await expect( getSummaryCount() ).toHaveText(
			`0/${ SUMMARY_MAX_LENGTH } characters`
		);
	} );

	test( 'should cut the Summary at the character limit', async () => {
		await gotoNewPost();

		await getShareToggle().check();
		await getSummary().fill( 'a'.repeat( SUMMARY_MAX_LENGTH + 100 ) );

		await expect( getSummaryCount() ).toHaveText(
			`${ SUMMARY_MAX_LENGTH }/${ SUMMARY_MAX_LENGTH } characters`
		);
		await expect( getSummary() ).toHaveValue(
			'a'.repeat( SUMMARY_MAX_LENGTH )
		);
	} );

	test( 'should send the share choice and the text with the post when publishing with the toggle on', async () => {
		await gotoNewPost();
		await fillTitle();
		await getShareToggle().check();
		await getSummary().fill( SUMMARY );

		const request = await publishAndGetSaveRequest();
		const { meta } = request.postDataJSON();

		expect( meta[ SHARE_META_KEY ] ).toBe( true );
		expect( meta[ SUMMARY_META_KEY ] ).toBe( SUMMARY );

		const postId = Number(
			new URL( page.url() ).searchParams.get( 'post' )
		);
		const saved = await getBusinessProfileShareState( postId );

		expect( saved.isShareEnabled ).toBe( true );
		expect( saved.summary ).toBe( SUMMARY );
		// A send was scheduled: the outcome is "queued", or already a failure because the
		// blocked Connect Server turned the background job away.
		expect( saved.syncOutcome ).not.toBeNull();
		expect( saved.syncOutcome ).not.toBe( 'empty-summary' );
	} );

	test( 'should not schedule a send when publishing with the toggle on and no text', async () => {
		await gotoNewPost();
		await fillTitle();
		await getShareToggle().check();

		const request = await publishAndGetSaveRequest();
		const { meta } = request.postDataJSON();

		expect( meta[ SHARE_META_KEY ] ).toBe( true );
		expect( meta[ SUMMARY_META_KEY ] ?? '' ).toBe( '' );

		const postId = Number(
			new URL( page.url() ).searchParams.get( 'post' )
		);
		const saved = await getBusinessProfileShareState( postId );

		expect( saved.syncOutcome ).toBe( 'empty-summary' );
	} );

	test( 'should not share the post when publishing with the toggle off', async () => {
		await gotoNewPost();
		await fillTitle();

		await publishAndGetSaveRequest();

		const postId = Number(
			new URL( page.url() ).searchParams.get( 'post' )
		);
		const saved = await getBusinessProfileShareState( postId );

		expect( saved.isShareEnabled ).toBe( false );
		expect( saved.syncOutcome ).toBeNull();
	} );

	test( 'should show the fixed tip next to the Summary label', async () => {
		await gotoNewPost();

		await getShareToggle().check();

		await expect(
			getPanel().getByRole( 'img', {
				name: 'Place critical promotional copy within the first 80–100 characters.',
			} )
		).toBeVisible();
	} );

	test( 'should fill the Summary from the title and body, and ask before replacing existing text', async () => {
		await gotoNewPost();
		await fillTitle( 'Sourdough Saturdays' );
		await getShareToggle().check();
		await editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: 'Fresh loaves every Saturday morning.' },
		} );
		// Inserting a block selects it, which switches the sidebar to its Block tab.
		await page
			.getByRole( 'region', { name: 'Editor settings' } )
			.getByRole( 'tab', { name: 'Post' } )
			.click();

		const generateButton = getPanel().getByRole( 'button', {
			name: 'Generate from post',
		} );

		await generateButton.click();

		await expect( getSummary() ).toHaveValue(
			'Sourdough Saturdays\n\nFresh loaves every Saturday morning.'
		);

		await getSummary().fill( 'My own words' );
		await generateButton.click();

		const dialog = page.getByRole( 'dialog', {
			name: 'Replace your text?',
		} );

		await expect( dialog ).toBeVisible();

		await dialog.getByRole( 'button', { name: 'Cancel' } ).click();

		await expect( getSummary() ).toHaveValue( 'My own words' );

		await generateButton.click();
		await dialog.getByRole( 'button', { name: 'Replace' } ).click();

		await expect( getSummary() ).toHaveValue(
			'Sourdough Saturdays\n\nFresh loaves every Saturday morning.'
		);
	} );

	test( 'should keep the choice and the text after publishing and reopening the post', async () => {
		await gotoNewPost();
		await fillTitle();
		await getShareToggle().check();
		await getSummary().fill( SUMMARY );

		await publishAndGetSaveRequest();
		await page.reload();
		await editor.openDocumentSettingsSidebar();

		await expect( getShareToggle() ).toBeChecked();
		await expect( getSummary() ).toHaveValue( SUMMARY );
	} );

	test( 'should keep the choice but not schedule a send when saving a draft with the toggle on', async () => {
		await gotoNewPost();
		await fillTitle();
		await getShareToggle().check();
		await getSummary().fill( SUMMARY );

		await editor.saveDraft();

		const postId = Number(
			new URL( page.url() ).searchParams.get( 'post' )
		);
		const saved = await getBusinessProfileShareState( postId );

		expect( saved.isShareEnabled ).toBe( true );
		expect( saved.summary ).toBe( SUMMARY );
		expect( saved.syncOutcome ).toBeNull();
	} );

	test( 'should not show the panel when no location is connected', async () => {
		await clearBusinessProfileLocation();

		try {
			await page.goto( '/wp-admin/post-new.php' );
			await editor.openDocumentSettingsSidebar();

			await expect(
				page
					.getByRole( 'region', { name: 'Editor settings' } )
					.getByRole( 'button', { name: 'Google Business Profile' } )
			).toHaveCount( 0 );
		} finally {
			await setBusinessProfileLocation();
		}
	} );
} );
