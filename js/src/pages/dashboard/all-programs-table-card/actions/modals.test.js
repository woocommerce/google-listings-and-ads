/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import EditProgramModal from './edit-program-modal';
import PauseProgramModal from './pause-program-modal';
import RemoveProgramModal from './remove-program-modal';
import { useAppDispatch } from '~/data';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useEuPoliticalDeclarationContext from '~/hooks/useEuPoliticalDeclarationContext';
import { recordGlaEvent } from '~/utils/tracks';
import { getEditCampaignUrl } from '~/utils/urls';

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getHistory: jest.fn(),
} ) );

jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn(),
} ) );

jest.mock( '~/hooks/useDispatchCoreNotices', () => jest.fn() );

jest.mock( '~/hooks/useEuPoliticalDeclarationContext', () => jest.fn() );

jest.mock( '~/utils/tracks', () => ( {
	recordGlaEvent: jest.fn(),
} ) );

const program = { id: 12, name: 'Campaign' };

describe( 'Program action modals', () => {
	let closeModal;
	let updateAdsCampaign;
	let deleteAdsCampaign;
	let createNotice;
	let handleError;
	let push;

	beforeEach( () => {
		closeModal = jest.fn();
		updateAdsCampaign = jest.fn().mockResolvedValue();
		deleteAdsCampaign = jest.fn().mockResolvedValue();
		createNotice = jest.fn();
		handleError = jest.fn();
		push = jest.fn();

		useAppDispatch.mockReturnValue( {
			updateAdsCampaign,
			deleteAdsCampaign,
		} );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );
		useEuPoliticalDeclarationContext.mockReturnValue( { handleError } );
		getHistory.mockReturnValue( { push } );
		recordGlaEvent.mockClear();
	} );

	describe( 'EditProgramModal', () => {
		it( 'navigates to the edit page and records the event when continuing', async () => {
			render(
				<EditProgramModal
					items={ [ program ] }
					closeModal={ closeModal }
				/>
			);

			await userEvent.click(
				screen.getByRole( 'button', { name: 'Continue to edit' } )
			);

			const url = getEditCampaignUrl( 12 );
			expect( push ).toHaveBeenCalledWith( url );
			expect( recordGlaEvent ).toHaveBeenCalledWith(
				'gla_dashboard_edit_program_click',
				{ program_id: 12, url }
			);
		} );

		it( 'closes without navigating when not editing', async () => {
			render(
				<EditProgramModal
					items={ [ program ] }
					closeModal={ closeModal }
				/>
			);

			await userEvent.click(
				screen.getByRole( 'button', { name: `Don't edit` } )
			);

			expect( closeModal ).toHaveBeenCalledTimes( 1 );
			expect( push ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'PauseProgramModal', () => {
		it( 'pauses the campaign, closes, and announces it', async () => {
			render(
				<PauseProgramModal
					items={ [ program ] }
					closeModal={ closeModal }
				/>
			);

			await userEvent.click(
				screen.getByRole( 'button', { name: 'Pause Campaign' } )
			);

			expect( updateAdsCampaign ).toHaveBeenCalledWith( 12, {
				status: 'paused',
			} );
			expect( closeModal ).toHaveBeenCalledTimes( 1 );
			await waitFor( () =>
				expect( createNotice ).toHaveBeenCalledWith(
					'success',
					'Campaign paused.',
					{ type: 'snackbar' }
				)
			);
		} );

		it( 'passes a failed update to the EU political declaration handler', async () => {
			const error = new Error( 'missing declaration' );
			updateAdsCampaign.mockRejectedValue( error );

			render(
				<PauseProgramModal
					items={ [ program ] }
					closeModal={ closeModal }
				/>
			);

			await userEvent.click(
				screen.getByRole( 'button', { name: 'Pause Campaign' } )
			);

			await waitFor( () =>
				expect( handleError ).toHaveBeenCalledWith( error )
			);
			expect( createNotice ).not.toHaveBeenCalled();
		} );

		it( 'closes without pausing when keeping the campaign active', async () => {
			render(
				<PauseProgramModal
					items={ [ program ] }
					closeModal={ closeModal }
				/>
			);

			await userEvent.click(
				screen.getByRole( 'button', { name: 'Keep Active' } )
			);

			expect( closeModal ).toHaveBeenCalledTimes( 1 );
			expect( updateAdsCampaign ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'RemoveProgramModal', () => {
		it( 'disables both buttons while deleting, then closes on success', async () => {
			let resolveDelete;
			deleteAdsCampaign.mockReturnValue(
				new Promise( ( resolve ) => ( resolveDelete = resolve ) )
			);

			render(
				<RemoveProgramModal
					items={ [ program ] }
					closeModal={ closeModal }
				/>
			);

			await userEvent.click(
				screen.getByRole( 'button', { name: 'Remove Campaign' } )
			);

			expect( deleteAdsCampaign ).toHaveBeenCalledWith( 12 );
			expect(
				screen.getByRole( 'button', { name: 'Keep Campaign' } )
			).toBeDisabled();
			expect(
				screen.getByRole( 'button', { name: /Remove Campaign/ } )
			).toBeDisabled();
			expect( closeModal ).not.toHaveBeenCalled();

			resolveDelete();
			await waitFor( () => expect( closeModal ).toHaveBeenCalled() );
		} );

		it( 'stays open and re-enables the buttons when deleting fails', async () => {
			deleteAdsCampaign.mockRejectedValue( new Error( 'failed' ) );

			render(
				<RemoveProgramModal
					items={ [ program ] }
					closeModal={ closeModal }
				/>
			);

			await userEvent.click(
				screen.getByRole( 'button', { name: 'Remove Campaign' } )
			);

			await waitFor( () =>
				expect(
					screen.getByRole( 'button', { name: 'Keep Campaign' } )
				).toBeEnabled()
			);
			expect( closeModal ).not.toHaveBeenCalled();
		} );
	} );
} );
