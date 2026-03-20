/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

/**
 * Custom hook to retrieve CYO incentives from the store.
 *
 * @return {Object} An object containing the CYO incentives data and a flag indicating if the resolution has finished.
 */
const useCYOIncentives = () => {
	return useSelect( ( select ) => {
		const { getCYOIncentives, hasFinishedResolution } = select( STORE_KEY );
		const data = getCYOIncentives();

		return {
			data,
			hasFinishedResolution: hasFinishedResolution( 'getCYOIncentives' ),
		};
	} );
};

export default useCYOIncentives;
