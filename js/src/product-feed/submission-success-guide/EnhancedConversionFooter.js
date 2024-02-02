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
	const { acceptedCustomerDataTerms: hasAcceptedTerms } =
		useAcceptedCustomerDataTerms();

	const handleOnClick = useCallback( () => {
		if ( hasAcceptedTerms ) {
			console.log( 'jere' );
		}
	}, [ hasAcceptedTerms ] );

	return (
		<>
			<div className="gla-submission-success-guide__space_holder" />
			<AppButton onClick={ handleOnClick } isPrimary>
				{ ! hasAcceptedTerms &&
					__(
						'Sign terms of service on Google Ads',
						'google-listings-and-ads'
					) }

				{ hasAcceptedTerms &&
					__( 'Confirm', 'google-listings-and-ads' ) }
			</AppButton>
		</>
	);
};

export default EnhancedConversionFooter;
