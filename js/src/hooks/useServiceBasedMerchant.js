/**
 * Internal dependencies
 */
import { glaData } from '~/constants';

const useServiceBasedMerchant = () => {
	console.log( 'useServiceBasedMerchant', JSON.stringify( glaData ) );
	return glaData.serviceBasedMerchant;
};

export default useServiceBasedMerchant;
