/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useDispatchOptions from '../../hooks/useDispatchOptions';

/**
 * @typedef {Object} UsePageStepResult
 * @property {string} pageStep - The value to control the stepper that the user is seeing.
 * @property {Function} updatePageStep - Function to update pageStep and save it with Options API when needed.
 */

/**
 * A custom React hook to access and update pageStep value to be used in `Stepper`.
 *
 * Upon calling update, it will save the pageStep value using Options API when needed.
 * The saved value can be retrieved later.
 * This is used for scenarios such as when users leave the stepper process
 * and come back again another time.
 *
 * @param {string} savedStep Initial pageStep value. This should come from a previously saved step value from Options API.
 * @return {UsePageStepResult} usePageStepResult.
 */
const usePageStep = ( savedStep ) => {
	// pageStep is used to control the current step
	// that the user is seeing.
	// pageStep should always be <= savedStep.
	const [ pageStep, setPageStep ] = useState( savedStep );
	const { updateOptions } = useDispatchOptions();

	const updatePageStep = ( step ) => {
		setPageStep( step );

		if ( parseInt( savedStep, 10 ) < parseInt( step, 10 ) ) {
			updateOptions( {
				gla_setup_mc_saved_step: step,
			} );
		}
	};

	return {
		pageStep,
		updatePageStep,
	};
};

export default usePageStep;
