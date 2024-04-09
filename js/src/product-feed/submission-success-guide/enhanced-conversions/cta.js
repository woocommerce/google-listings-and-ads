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
import Enable from './enable';
import Confirm from './confirm';

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
			<Enable
				onEnableEnhancedConversion={ handleOnEnableEnhancedConversions }
			/>
		);
	}

	return <Confirm onConfirm={ onConfirm } />;
};

export default CTA;
