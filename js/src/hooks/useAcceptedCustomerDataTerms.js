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
		const hasFinishedResolution = select( STORE_KEY ).hasFinishedResolution(
			'getAcceptedCustomerDataTerms'
		);

		return {
			acceptedCustomerDataTerms,
			hasFinishedResolution,
		};
	}, [] );
};

export default useAcceptedCustomerDataTerms;
