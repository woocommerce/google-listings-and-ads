/**
 * External dependencies
 */
import { noop } from 'lodash';
import { useCallback, useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useTermsPolling from './useTermsPolling';
import EnableButton from './enable-button';
import ConfirmButton from './confirm-button';

const CTA = ( { onConfirm = noop } ) => {
	const [ startBackgroundPoll, setStartBackgroundPoll ] = useState( false );
	const { acceptedCustomerDataTerms, hasFinishedResolution } =
		useAcceptedCustomerDataTerms();
	useTermsPolling( startBackgroundPoll );

	// Turn off polling when the user has accepted the terms.
	useEffect( () => {
		if ( acceptedCustomerDataTerms && startBackgroundPoll ) {
			setStartBackgroundPoll( false );
		}
	}, [ acceptedCustomerDataTerms, startBackgroundPoll ] );

	const handleOnEnableEnhancedConversions = useCallback( () => {
		setStartBackgroundPoll( true );
	}, [] );

	if ( ! hasFinishedResolution ) {
		return null;
	}

	if ( startBackgroundPoll ) {
		return <AppButton isSecondary disabled loading />;
	}

	if ( ! acceptedCustomerDataTerms ) {
		return (
			<EnableButton
				onEnableEnhancedConversion={ handleOnEnableEnhancedConversions }
			/>
		);
	}

	return <ConfirmButton onConfirm={ onConfirm } />;
};

export default CTA;
