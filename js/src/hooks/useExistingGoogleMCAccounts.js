/**
 * Internal dependencies
 */
import useAppSelectDispatch from './useAppSelectDispatch';

const useExistingGoogleMCAccounts = () => {
	return useAppSelectDispatch( 'getExistingGoogleMCAccounts' );
};

export default useExistingGoogleMCAccounts;
