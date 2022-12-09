/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';

/**
 * @typedef {import('react').React} React
 */

/**
 * @typedef {import('.~/data/selectors').Tour} Tour
 */

/**
 * @typedef {Object} TourHook
 * @property { { data: Tour, hasFinishedResolution: boolean, isResolving: boolean, invalidateResolution: Function } } tour The tour object
 * @property {React.Dispatch<React.SetStateAction<Tour>>} setTour Setter for the hook
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

	const showTour =
		tour.hasFinishedResolution && !! tour.data && ! tour.data.checked;

	console.log( tourId, {
		tour,
		setTour: upsertTour,
		showTour,
	} );
	return {
		tour,
		setTour: upsertTour,
		showTour,
	};
};

export default useTour;
