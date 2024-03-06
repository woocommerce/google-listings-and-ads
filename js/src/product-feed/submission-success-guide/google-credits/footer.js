/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { GUIDE_NAMES } from '.~/constants';
import { GLA_MODAL_CLOSED_EVENT_NAME } from '../constants';
import AppButton from '.~/components/app-button';
import AddPaidCampaignButton from '.~/components/paid-ads/add-paid-campaign-button';

const Footer = ( { onModalClose = noop } ) => {
	return (
		<Fragment>
			<div className="gla-submission-success-guide__space_holder" />

			<AppButton
				isSecondary
				data-action="maybe-later"
				onClick={ onModalClose }
			>
				{ __( 'Maybe later', 'google-listings-and-ads' ) }
			</AppButton>

			<AddPaidCampaignButton
				isPrimary
				isSecondary={ false }
				isSmall={ false }
				eventName={ GLA_MODAL_CLOSED_EVENT_NAME }
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
