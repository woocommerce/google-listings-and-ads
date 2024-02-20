/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { GUIDE_NAMES } from '.~/constants';
import { EVENT_NAME } from '../constants';
import AppButton from '.~/components/app-button';
import AddPaidCampaignButton from '.~/components/paid-ads/add-paid-campaign-button';

const Footer = ( { handleGuideFinish } ) => {
	return (
		<Fragment>
			<div className="gla-submission-success-guide__space_holder" />

			<AppButton
				isSecondary
				data-action="maybe-later"
				onClick={ handleGuideFinish }
			>
				{ __( 'Maybe later', 'google-listings-and-ads' ) }
			</AppButton>

			<AddPaidCampaignButton
				isPrimary
				isSecondary={ false }
				isSmall={ false }
				eventName={ EVENT_NAME }
				eventProps={ {
					context: GUIDE_NAMES.SUBMISSION_SUCCESS,
					action: 'create-paid-campaign',
				} }
			>
				{ __( 'Create paid campaign', 'google-listings-and-ads' ) }
			</AddPaidCampaignButton>
		</Fragment>
	);
};

export default Footer;
