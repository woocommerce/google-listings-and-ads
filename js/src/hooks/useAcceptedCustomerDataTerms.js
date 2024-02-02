/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const useAcceptedCustomerDataTerms = () => {
	return useSelect( ( select ) => {
		const acceptedCustomerDataTerms =
			select( STORE_KEY ).getAcceptedCustomerDataTerms();
		const isResolving = select( STORE_KEY ).isResolving(
			'getAcceptedCustomerDataTerms'
		);

		return { acceptedCustomerDataTerms, isResolving };
	}, [] );
};

export default useAcceptedCustomerDataTerms;
