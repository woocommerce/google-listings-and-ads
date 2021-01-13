/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';

/**
 * A custom React hook to get the following:
 *
 * - `pageStep`: a value to control the stepper that the user is seeing.
 * - `updatePageStep`: a function to update the pageStep value and save it with Options API.
 *
 * @param {string} savedStep Initial pageStep value. This should come from a previously saved step value from Options API.
 */
const usePageStep = ( savedStep ) => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	// pageStep is used to control the current step
	// that the user is seeing.
	// pageStep should always be <= savedStep.
	const [ pageStep, setPageStep ] = useState( savedStep );

	const updatePageStep = ( stepKey ) => {
		setPageStep( stepKey );

		if ( parseInt( savedStep, 10 ) < parseInt( stepKey, 10 ) ) {
			updateOptions( {
				gla_setup_mc_saved_step: stepKey,
			} );
		}
	};

	return {
		pageStep,
		updatePageStep,
	};
};

export default usePageStep;
