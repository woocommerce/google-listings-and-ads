/**
 * External dependencies
 */
import { noop } from 'lodash';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import EnableButton from './enable-button';
import ConfirmButton from './confirm-button';
import useAutoCheckEnhancedConversionTOS from '.~/hooks/useAutoCheckEnhancedConversionTOS';

const CTA = ( { onConfirm = noop } ) => {
	// We could have used the return value from useAutoCheckEnhancedConversionTOS to know
	// if there's polling in progress. However from a UI point of view, it'll be confusing
	// for the user when refreshing the page to see a loading spinner while polling is in progress.
	// Hence we are showing the spinner only when the user clicks on the Enable button.
	const [ isPolling, setIsPolling ] = useState( false );
	const { acceptedCustomerDataTerms, hasFinishedResolution } =
		useAcceptedCustomerDataTerms();
	useAutoCheckEnhancedConversionTOS();

	useEffect( () => {
		// As soon as the terms are accepted, do not show the spinner
		if ( acceptedCustomerDataTerms ) {
			setIsPolling( false );
		}
	}, [ acceptedCustomerDataTerms ] );

	const handleOnEnable = () => {
		setIsPolling( true );
	};

	if ( ! hasFinishedResolution ) {
		return null;
	}

	if ( isPolling ) {
		return <AppButton isSecondary disabled loading />;
	}

	if ( ! acceptedCustomerDataTerms ) {
		return <EnableButton onEnable={ handleOnEnable } />;
	}

	return <ConfirmButton onConfirm={ onConfirm } />;
};

export default CTA;
