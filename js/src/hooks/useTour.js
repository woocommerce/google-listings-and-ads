/**
 * External dependencies
 */
import { useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';

/**
 * @typedef {import('.~/data/selectors').Tour} Tour
 */

/**
 * @typedef {Object} TourHook
 * @property { { data: Tour|null, hasFinishedResolution: boolean, isResolving: boolean, invalidateResolution: Function } } tour The tour object
 * @property {() => Promise<{tour: Tour}>} setTourChecked Setter for updating the checked state of the tour in use.
 * @property {boolean} showTour Indicates if the tour should be shown based on it's checked prop
 */

/**
 * This hook returns a tour status based on a Tour ID, a setter for the tour and a property indicating if the tour should be shown.
 *
 * @param {string} tourId Tour ID to get
 * @return {TourHook} The tour Hook
 */
const useTour = ( tourId ) => {
	const tour = useAppSelectDispatch( 'getTour', tourId );
	const { upsertTour } = useAppDispatch();
	const [ tourClosed, setTourClosed ] = useState( false );
	const checked = tour.data?.checked;
	const showTour = tour.hasFinishedResolution && ! checked && ! tourClosed;

	const closeTour = useCallback( () => {
		const nextTour = { id: tourId, checked: true };

		if ( ! checked ) {
			setTourClosed( true );
			return upsertTour( nextTour );
		}

		return Promise.resolve( { tour: nextTour } );
	}, [ checked, setTourClosed, upsertTour, tourId ] );

	return {
		tour,
		closeTour,
		showTour,
	};
};

export default useTour;
