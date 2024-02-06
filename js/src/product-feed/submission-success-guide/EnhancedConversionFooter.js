/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';

const EnhancedConversionFooter = () => {
	const {
		acceptedCustomerDataTerms: hasAcceptedTerms,
		hasFinishedResolution,
	} = useAcceptedCustomerDataTerms();

	const handleOnClick = useCallback( () => {
		if ( hasAcceptedTerms ) {
			console.log( 'Redirect the user to the TOS page.' );
		}
	}, [ hasAcceptedTerms ] );

	return (
		<>
			<div className="gla-submission-success-guide__space_holder" />

			{ ! hasFinishedResolution && <AppButton isPrimary loading /> }

			{ hasFinishedResolution && (
				<AppButton onClick={ handleOnClick } isPrimary>
					{ ! hasAcceptedTerms &&
						__(
							'Sign terms of service on Google Ads',
							'google-listings-and-ads'
						) }

					{ hasAcceptedTerms &&
						__( 'Confirm', 'google-listings-and-ads' ) }
				</AppButton>
			) }
		</>
	);
};

export default EnhancedConversionFooter;
