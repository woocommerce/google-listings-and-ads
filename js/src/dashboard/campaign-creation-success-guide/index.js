/**
 * External dependencies
 */
import { getHistory, getNewPath, getQuery } from '@woocommerce/navigation';
import { createInterpolateElement, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { getCreateCampaignUrl } from '.~/utils/urls';
import AppModal from '.~/components/app-modal';
import GuidePageContent, {
	ContentLink,
} from '.~/components/guide-page-content';
import { GUIDE_NAMES } from '.~/constants';
import headerImageURL from './header.svg';
import './index.scss';

const CTA_CREATE_ANOTHER_CAMPAIGN = 'create-another-campaign';

const handleCloseWithAction = ( e, specifiedAction ) => {
	const action = specifiedAction || e.currentTarget.dataset.action;
	const nextQuery = {
		...getQuery(),
		guide: undefined,
	};
	getHistory().replace( getNewPath( nextQuery ) );

	if ( action === CTA_CREATE_ANOTHER_CAMPAIGN ) {
		getHistory().push( getCreateCampaignUrl() );
	}

	recordEvent( 'gla_modal_closed', {
		context: GUIDE_NAMES.CAMPAIGN_CREATION_SUCCESS,
		action,
	} );
};

const handleRequestClose = ( e ) => handleCloseWithAction( e, 'dismiss' );

const GuideImplementation = () => {
	useEffect( () => {
		recordEvent( 'gla_modal_open', {
			context: GUIDE_NAMES.CAMPAIGN_CREATION_SUCCESS,
		} );
	}, [] );

	return (
		<AppModal
			className="gla-campaign-creation-success-guide"
			onRequestClose={ handleRequestClose }
			buttons={ [
				<Button
					key="0"
					isTertiary
					data-action={ CTA_CREATE_ANOTHER_CAMPAIGN }
					onClick={ handleCloseWithAction }
				>
					{ __(
						'Create another campaign',
						'google-listings-and-ads'
					) }
				</Button>,
				<Button
					key="1"
					isPrimary
					data-action="confirm"
					onClick={ handleCloseWithAction }
				>
					{ __( 'Got it', 'google-listings-and-ads' ) }
				</Button>,
			] }
		>
			<div className="gla-campaign-creation-success-guide__header-image">
				<img
					src={ headerImageURL }
					alt={ __(
						'Drawing of a person who successfuly launched a campaign',
						'google-listings-and-ads'
					) }
					width="413"
					height="160"
				/>
			</div>
			<GuidePageContent
				title={ __(
					`You've set up a paid Smart Shopping Campaign!`,
					'google-listings-and-ads'
				) }
			>
				{ createInterpolateElement(
					__(
						'You can pause or edit your campaign at any time. For best results, we recommend allowing your campaign to run for at least 14 days without pausing or editing. <link>Learn more about Smart Shopping technology.</link>',
						'google-listings-and-ads'
					),
					{
						link: (
							<ContentLink
								href="https://support.google.com/google-ads/answer/7674739"
								context="campaign-creation-smart-shopping"
							/>
						),
					}
				) }
			</GuidePageContent>
		</AppModal>
	);
};

/**
 * Modal window to prompt the user at Dashboard, after successful completing the campaign creation.
 *
 * Show this guide modal by visiting the path with a specific query `guide=campaign-creation-success`.
 * For example: `/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fdashboard&guide=campaign-creation-success`.
 */
export default function CampaignCreationSuccessGuide() {
	const isOpen = getQuery().guide === GUIDE_NAMES.CAMPAIGN_CREATION_SUCCESS;

	if ( ! isOpen ) {
		return null;
	}
	return <GuideImplementation />;
}
