/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';

const useGetOption = ( optionName ) => {
	return useSelect( ( select ) => {
		const { getOption, isResolving } = select( OPTIONS_STORE_NAME );

		const data = getOption( optionName );
		const loading = isResolving( 'getOption', [ optionName ] );

		return {
			loading,
			data,
		};
	} );
};

export default useGetOption;
