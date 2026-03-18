/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

const useCYOIncentives = () => {
	const payload = useAppSelectDispatch( 'getCYOIncentives' );
	return payload.data;
};

export default useCYOIncentives;
