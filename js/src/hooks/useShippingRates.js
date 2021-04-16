/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data';

const emptyList = [];

const useShippingRates = () => {
	return useSelect( ( select ) => {
		const {
			getShippingRates,
			getError,
			isResolving,
			hasFinishedResolution,
		} = select( STORE_KEY );

		const list = getShippingRates();

		return {
			/*
			  ## Required expose
			 */
			// - Always returns an array to make it easier to use
			//    when the consuming side doesn't care whether the data loaded or not.
			// - Named `list` for array type instead of `data`
			list: list || emptyList,

			// - It'd be `false` before and during requesting
			// - Return `true` after request finished regardless the request is success or failure
			loaded: hasFinishedResolution( 'getShippingRates' ),

			/*
			  ## Expose if need
			 */
			// Use this when need to know whether it's during resolving within wp.data exactly
			loading: isResolving( 'getShippingRates' ),

			// - Return `true` only if the data has been ever successfully loaded; otherwise, return 'false'
			// - Use this when need to know if the data hasn't yet been successfully loaded (it's still `null`)
			available: Boolean( list ),

			// Return the latest error object if error occurred; otherwise, return `null`
			error: getError( 'getShippingRates' ),
		};
	}, [] );
};

export default useShippingRates;
