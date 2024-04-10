/**
 * External dependencies
 */
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import EnableButton from './enable-button';
import ConfirmButton from './confirm-button';
import useAutoCheckEnhancedConversionTOS from '.~/hooks/useAutoCheckEnhancedConversionTOS';

const CTA = ( { onConfirm = noop } ) => {
	const { acceptedCustomerDataTerms, hasFinishedResolution } =
		useAcceptedCustomerDataTerms();
	const isPolling = useAutoCheckEnhancedConversionTOS();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	if ( isPolling ) {
		return <AppButton isSecondary disabled loading />;
	}

	if ( ! acceptedCustomerDataTerms ) {
		return <EnableButton />;
	}

	return <ConfirmButton onConfirm={ onConfirm } />;
};

export default CTA;
