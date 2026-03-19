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
 * @return {Object|null} The CYO incentives. It will be `null` if not yet fetched or fetched but doesn't exist.
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
