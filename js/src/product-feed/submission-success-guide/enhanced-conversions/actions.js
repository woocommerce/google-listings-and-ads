/**
 * External dependencies
 */
import { noop } from 'lodash';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ENHANCED_ADS_CONVERSION_STATUS } from '.~/constants';
import AppButton from '.~/components/app-button';
import CTA from '.~/components/enhanced-conversion-tracking-settings/cta';
import useAcceptedCustomerDataTerms from '.~/hooks/useAcceptedCustomerDataTerms';
import useAllowEnhancedConversions from '.~/hooks/useAllowEnhancedConversions';

const Actions = ( { onModalClose = noop } ) => {
	const { acceptedCustomerDataTerms, hasFinishedResolution } =
		useAcceptedCustomerDataTerms();
	const {
		allowEnhancedConversions,
		hasFinishedResolution: hasResolvedAllowEnhancedConversions,
	} = useAllowEnhancedConversions();

	const getCTA = () => {
		if (
			! hasFinishedResolution ||
			! hasResolvedAllowEnhancedConversions
		) {
			return null;
		}

		if (
			! acceptedCustomerDataTerms ||
			( acceptedCustomerDataTerms &&
				allowEnhancedConversions !==
					ENHANCED_ADS_CONVERSION_STATUS.ENABLED )
		) {
			return <CTA />;
		}

		return (
			<AppButton isSecondary data-action="close" onClick={ onModalClose }>
				{ __( 'Close', 'google-listings-and-ads' ) }
			</AppButton>
		);
	};

	return (
		<Fragment>
			<div className="gla-submission-success-guide__space_holder" />

			{ getCTA() }
		</Fragment>
	);
};

export default Actions;
