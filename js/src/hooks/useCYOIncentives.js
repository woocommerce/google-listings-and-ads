/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

/**
 * Custom hook to retrieve CYO incentives from the store.
 * 
 * @return {Object|null} The CYO incentives. It will be `null` if not yet fetched or fetched but doesn't exist.
 */
const useCYOIncentives = () => {
	const payload = useAppSelectDispatch( 'getCYOIncentives' );

    return {
		...payload,
		data: payload.data?.incentives || null,
	};
};

export default useCYOIncentives;
