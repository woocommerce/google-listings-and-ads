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
import AppModal from '.~/components/app-modal';
import GuidePageContent, {
	ContentLink,
} from '.~/components/guide-page-content';
import { ReactComponent as HeaderSvg } from './header.svg';
import './index.scss';

const GUIDE_NAME = 'campaign-creation-success';
const CTA_CREATE_ANOTHER_CAMPAIGN = 'create-another-campaign';

// TODO: The current close method is temporarily for demo.
//       Need to reconsider how this guide modal would be triggered later.
const handleCloseWithAction = ( e, specifiedAction ) => {
	const action = specifiedAction || e.currentTarget.dataset.action;
	const nextQuery = {
		...getQuery(),
		guide: undefined,
	};
	getHistory().replace( getNewPath( nextQuery ) );

	if ( action === CTA_CREATE_ANOTHER_CAMPAIGN ) {
		// TODO: Mutate nextQuery to direct user to campaign creation path after that page ready.
		getHistory().push( getNewPath( nextQuery ) );
	}

	recordEvent( 'gla_modal_closed', {
		context: GUIDE_NAME,
		action,
	} );
};

const handleRequestClose = ( e ) => handleCloseWithAction( e, 'dismiss' );

const GuideImplementation = () => {
	useEffect( () => {
		recordEvent( 'gla_modal_open', { context: GUIDE_NAME } );
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
				<HeaderSvg viewBox="0 0 413 160" />
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
 *
 * TODO: The current open method is temporarily for demo.
 *       Need to reconsider how this guide modal would be triggered later.
 */
export default function CampaignCreationSuccessGuide() {
	const isOpen = getQuery().guide === GUIDE_NAME;

	if ( ! isOpen ) {
		return null;
	}
	return <GuideImplementation />;
}
