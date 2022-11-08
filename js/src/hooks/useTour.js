/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';

/**
 * Returns the Attribute Mapping Rules
 *
 * @param {string} tourId Tour ID to get
 * @return {Object} The tour.
 */
const useTour = ( tourId ) => {
	const data = useAppSelectDispatch( 'getTour', tourId );
	const { upsertTour } = useAppDispatch();
	return [ data, upsertTour ];
};

export default useTour;
