/**
 * External dependencies
 */
import { renderHook, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useProgramActions from './';
import EditProgramModal from './edit-program-modal';
import PauseProgramModal from './pause-program-modal';
import RemoveProgramModal from './remove-program-modal';
import { useAppDispatch } from '~/data';
import useEuPoliticalDeclarationContext from '~/hooks/useEuPoliticalDeclarationContext';

jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn(),
} ) );

jest.mock( '~/hooks/useEuPoliticalDeclarationContext', () => jest.fn() );

const productFeed = { id: 0, status: 'enabled', isFreeListings: true };
const enabledPmax = {
	id: 1,
	status: 'enabled',
	type: 'performance_max',
	isFreeListings: false,
};
const pausedShopping = {
	id: 2,
	status: 'paused',
	type: 'shopping',
	isFreeListings: false,
};

const eligibleActionIds = ( actions, item ) =>
	actions
		.filter( ( action ) => action.isEligible( item ) )
		.map( ( { id } ) => id );

describe( 'useProgramActions', () => {
	let updateAdsCampaign;
	let handleError;

	beforeEach( () => {
		updateAdsCampaign = jest.fn().mockResolvedValue();
		handleError = jest.fn();
		useAppDispatch.mockReturnValue( { updateAdsCampaign } );
		useEuPoliticalDeclarationContext.mockReturnValue( { handleError } );
	} );

	const getActions = () =>
		renderHook( () => useProgramActions() ).result.current;

	it( 'offers no action for the product feed', () => {
		expect( eligibleActionIds( getActions(), productFeed ) ).toEqual( [] );
	} );

	it( 'offers edit, pause, and remove for an enabled PMax campaign', () => {
		expect( eligibleActionIds( getActions(), enabledPmax ) ).toEqual( [
			'edit',
			'pause',
			'remove',
		] );
	} );

	it( 'offers resume and remove for a paused non-PMax campaign', () => {
		expect( eligibleActionIds( getActions(), pausedShopping ) ).toEqual( [
			'resume',
			'remove',
		] );
	} );

	it( 'makes edit the only primary action, with an icon', () => {
		const actions = getActions();
		const primary = actions.filter( ( action ) => action.isPrimary );

		expect( primary.map( ( { id } ) => id ) ).toEqual( [ 'edit' ] );
		expect( primary[ 0 ].icon ).toBeTruthy();
	} );

	it( 'marks remove as destructive and supports no bulk action', () => {
		const actions = getActions();

		expect(
			actions.find( ( { id } ) => id === 'remove' ).isDestructive
		).toBe( true );
		expect( actions.some( ( action ) => action.supportsBulk ) ).toBe(
			false
		);
	} );

	it( 'confirms edit, pause, and remove in modals with the existing titles', () => {
		const byId = Object.fromEntries(
			getActions().map( ( action ) => [ action.id, action ] )
		);

		expect( byId.edit ).toMatchObject( {
			RenderModal: EditProgramModal,
			modalHeader: 'Before you edit…',
		} );
		expect( byId.pause ).toMatchObject( {
			RenderModal: PauseProgramModal,
			modalHeader: 'Before you pause…',
		} );
		expect( byId.remove ).toMatchObject( {
			RenderModal: RemoveProgramModal,
			modalHeader: 'Permanently Remove?',
		} );
	} );

	it( 'resumes a paused campaign without a confirmation', async () => {
		const resume = getActions().find( ( { id } ) => id === 'resume' );

		expect( resume.RenderModal ).toBeUndefined();
		await resume.callback( [ pausedShopping ] );

		expect( updateAdsCampaign ).toHaveBeenCalledWith( 2, {
			status: 'enabled',
		} );
	} );

	it( 'passes a failed resume to the EU political declaration handler', async () => {
		const error = new Error( 'missing declaration' );
		updateAdsCampaign.mockRejectedValue( error );
		const resume = getActions().find( ( { id } ) => id === 'resume' );

		resume.callback( [ pausedShopping ] );

		await waitFor( () =>
			expect( handleError ).toHaveBeenCalledWith( error )
		);
	} );
} );
