/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	clearBusinessProfileLocation,
	getBusinessProfileShareState,
	setBusinessProfileLocation,
} from '../../utils/api';
import { getClassicPostEditorUtils } from '../../utils/post-editor';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

const SHARE_FIELD = 'gla_business_profile_share_share';
const SUMMARY_FIELD = 'gla_business_profile_share_summary';
const SUMMARY_MAX_LENGTH = 1500;
const SUMMARY = 'Fresh sourdough every morning. Come by before nine.';

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

/**
 * @type {ReturnType<typeof getClassicPostEditorUtils>} editorUtils
 */
let editorUtils = null;

// Not covered: the send to Google itself, which runs later as a background job, and whether the
// connected location can receive posts. The site cannot reach Google while the location is set.
test.describe( 'Google Business Profile publish toggle in the Classic Editor', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		editorUtils = getClassicPostEditorUtils( page );

		await editorUtils.useClassicEditor();
		await setBusinessProfileLocation();
	} );

	test.afterAll( async () => {
		await clearBusinessProfileLocation();
		await page.close();
	} );

	test( 'should show the toggle off, without the Summary field, on a new post', async () => {
		await editorUtils.gotoAddPostPage();

		await expect( editorUtils.getBusinessProfileMetaBox() ).toBeVisible();
		await expect(
			editorUtils.getBusinessProfileShareToggle()
		).not.toBeChecked();
		await expect(
			editorUtils
				.getBusinessProfileMetaBox()
				.getByText(
					"Share to Jane's Bakery as an update when you publish."
				)
		).toBeVisible();
		await expect( editorUtils.getBusinessProfileSummary() ).toBeHidden();
	} );

	test( 'should show the Summary field with its character count once the toggle is on', async () => {
		await editorUtils.gotoAddPostPage();

		await editorUtils.getBusinessProfileShareToggle().check();

		await expect( editorUtils.getBusinessProfileSummary() ).toBeVisible();
		await expect( editorUtils.getBusinessProfileSummaryCount() ).toHaveText(
			`0/${ SUMMARY_MAX_LENGTH } characters`
		);
	} );

	test( 'should cut the Summary at the character limit', async () => {
		await editorUtils.gotoAddPostPage();

		await editorUtils.getBusinessProfileShareToggle().check();
		await editorUtils
			.getBusinessProfileSummary()
			.fill( 'a'.repeat( SUMMARY_MAX_LENGTH + 100 ) );

		await expect( editorUtils.getBusinessProfileSummaryCount() ).toHaveText(
			`${ SUMMARY_MAX_LENGTH }/${ SUMMARY_MAX_LENGTH } characters`
		);
		await expect( editorUtils.getBusinessProfileSummary() ).toHaveValue(
			'a'.repeat( SUMMARY_MAX_LENGTH )
		);
	} );

	test( 'should send the share choice and the text with the post when publishing with the toggle on', async () => {
		await editorUtils.gotoAddPostPage();
		await editorUtils.fillTitle();
		await editorUtils.getBusinessProfileShareToggle().check();
		await editorUtils.getBusinessProfileSummary().fill( SUMMARY );

		const request = await editorUtils.publish();
		const form = new URLSearchParams( request.postData() );

		expect( form.get( SHARE_FIELD ) ).toBe( '1' );
		expect( form.get( SUMMARY_FIELD ) ).toBe( SUMMARY );

		await expect( page ).toHaveURL( /post\.php\?post=\d+&action=edit/ );

		const saved = await getBusinessProfileShareState(
			editorUtils.getPostID()
		);

		expect( saved.isShareEnabled ).toBe( true );
		expect( saved.summary ).toBe( SUMMARY );
		// A send was scheduled: the outcome is "queued", or already a failure because the
		// blocked Connect Server turned the background job away.
		expect( saved.syncOutcome ).not.toBeNull();
		expect( saved.syncOutcome ).not.toBe( 'empty-summary' );

		await expect(
			editorUtils.getBusinessProfileShareToggle()
		).toBeChecked();
		await expect( editorUtils.getBusinessProfileSummary() ).toHaveValue(
			SUMMARY
		);
	} );

	test( 'should not schedule a send when publishing with the toggle on and no text', async () => {
		await editorUtils.gotoAddPostPage();
		await editorUtils.fillTitle();
		await editorUtils.getBusinessProfileShareToggle().check();

		const request = await editorUtils.publish();
		const form = new URLSearchParams( request.postData() );

		expect( form.get( SHARE_FIELD ) ).toBe( '1' );
		expect( form.get( SUMMARY_FIELD ) ).toBe( '' );

		await expect( page ).toHaveURL( /post\.php\?post=\d+&action=edit/ );

		const saved = await getBusinessProfileShareState(
			editorUtils.getPostID()
		);

		expect( saved.syncOutcome ).toBe( 'empty-summary' );
	} );

	test( 'should not share the post when publishing with the toggle off', async () => {
		await editorUtils.gotoAddPostPage();
		await editorUtils.fillTitle();

		const request = await editorUtils.publish();
		const form = new URLSearchParams( request.postData() );

		expect( form.get( SHARE_FIELD ) ).toBe( '' );

		await expect( page ).toHaveURL( /post\.php\?post=\d+&action=edit/ );

		const saved = await getBusinessProfileShareState(
			editorUtils.getPostID()
		);

		expect( saved.isShareEnabled ).toBe( false );
		expect( saved.syncOutcome ).toBeNull();
	} );

	test( 'should show the fixed tip next to the Summary label', async () => {
		await editorUtils.gotoAddPostPage();

		await editorUtils.getBusinessProfileShareToggle().check();

		await expect(
			editorUtils.getBusinessProfileSummaryTip()
		).toBeVisible();
	} );

	test( 'should fill the Summary from the title and body, and ask before replacing existing text', async () => {
		await editorUtils.gotoAddPostPage();
		await editorUtils.fillTitle( 'Sourdough Saturdays' );
		await editorUtils.fillBody( 'Fresh loaves every Saturday morning.' );
		await editorUtils.getBusinessProfileShareToggle().check();

		await editorUtils.getGenerateFromPostButton().click();

		await expect( editorUtils.getBusinessProfileSummary() ).toHaveValue(
			'Sourdough Saturdays\n\nFresh loaves every Saturday morning.'
		);

		await editorUtils.getBusinessProfileSummary().fill( 'My own words' );
		await editorUtils.getGenerateFromPostButton().click();

		const dialog = page.getByRole( 'dialog', {
			name: 'Replace your text?',
		} );

		await expect( dialog ).toBeVisible();

		await dialog.getByRole( 'button', { name: 'Cancel' } ).click();

		await expect( editorUtils.getBusinessProfileSummary() ).toHaveValue(
			'My own words'
		);

		await editorUtils.getGenerateFromPostButton().click();
		await dialog.getByRole( 'button', { name: 'Replace' } ).click();

		await expect( editorUtils.getBusinessProfileSummary() ).toHaveValue(
			'Sourdough Saturdays\n\nFresh loaves every Saturday morning.'
		);
	} );

	test( 'should keep the choice but not schedule a send when saving a draft with the toggle on', async () => {
		await editorUtils.gotoAddPostPage();
		await editorUtils.fillTitle();
		await editorUtils.getBusinessProfileShareToggle().check();
		await editorUtils.getBusinessProfileSummary().fill( SUMMARY );

		await editorUtils.saveDraft();

		const saved = await getBusinessProfileShareState(
			editorUtils.getPostID()
		);

		expect( saved.isShareEnabled ).toBe( true );
		expect( saved.summary ).toBe( SUMMARY );
		expect( saved.syncOutcome ).toBeNull();
	} );

	test( 'should not show the meta box when no location is connected', async () => {
		await clearBusinessProfileLocation();

		try {
			await editorUtils.gotoAddPostPage();

			await expect( editorUtils.getBusinessProfileMetaBox() ).toHaveCount(
				0
			);
		} finally {
			await setBusinessProfileLocation();
		}
	} );
} );
