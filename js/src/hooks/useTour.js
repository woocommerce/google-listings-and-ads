/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';

/**
 * @typedef {Object} TourHook
 * @property {(nextChecked: boolean) => void} setTourChecked Setter for updating the checked state of the tour in use.
 * @property {boolean} tourChecked The checked state of the tour in use. It will be false before the actual tour data is fetched from API.
 */

/**
 * This hook returns the checked state of a tour based on the given tour ID,
 * and also a setter for updating the checked state.
 *
 * Please note that the first applicable state of checked is fetched from API, however,
 * the subsequent state updating to the wp-data store will be performed immediately,
 * and state updating to API will be performed next but won't sync back the result
 * to the wp-data store even if the update request fails.
 *
 * @param {string} tourId Tour ID to get
 * @return {TourHook} The tour Hook
 */
const useTour = ( tourId ) => {
	const tour = useAppSelectDispatch( 'getTour', tourId );
	const { upsertTour } = useAppDispatch();

	const checked = tour.data?.checked;
	const tourChecked = ! tour.hasFinishedResolution || Boolean( checked );

	const setTourChecked = useCallback(
		( nextChecked ) => {
			// Avoid API errors that request to update the same checked value.
			if ( nextChecked !== checked ) {
				upsertTour( { id: tourId, checked: nextChecked }, true );
			}
		},
		[ tourId, checked, upsertTour ]
	);

	return {
		tourChecked,
		setTourChecked,
	};
};

export default useTour;
