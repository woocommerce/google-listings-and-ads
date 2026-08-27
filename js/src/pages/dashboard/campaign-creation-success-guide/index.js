/**
 * External dependencies
 */
import {
	createInterpolateElement,
	useEffect,
	useCallback,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AppModal from '~/components/app-modal';
import GuidePageContent, { ContentLink } from '~/components/guide-page-content';
import { GUIDE_NAMES } from '~/constants';
import headerImageURL from '~/images/success-guide-header.svg';
import {
	CTA_CREATE_ANOTHER_CAMPAIGN,
	CTA_CONFIRM,
	CTA_DISMISS,
} from '../constants';
import { recordGlaEvent } from '~/utils/tracks';
import './index.scss';

/**
 * Modal window to prompt the user at Dashboard, after successful completing the campaign creation.
 *
 * Show this guide modal by visiting the path with a specific query `guide=campaign-creation-success`.
 * For example: `/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fdashboard&guide=campaign-creation-success`.
 *
 * @param {Object} props React component props.
 * @param {Function} props.onGuideRequestClose The function to be called when the guide is closed.
 */
export default function CampaignCreationSuccessGuide( {
	onGuideRequestClose = () => {},
} ) {
	useEffect( () => {
		recordGlaEvent( 'gla_modal_open', {
			context: GUIDE_NAMES.CAMPAIGN_CREATION_SUCCESS,
		} );
	}, [] );

	const handleRequestClose = useCallback(
		( e ) => onGuideRequestClose( e, CTA_DISMISS ),
		[ onGuideRequestClose ]
	);

	return (
		<AppModal
			buttons={ [
				<AppButton
					data-action={ CTA_CREATE_ANOTHER_CAMPAIGN }
					key="0"
					onClick={ onGuideRequestClose }
					isTertiary
				>
					{ __(
						'Create another campaign',
						'google-listings-and-ads'
					) }
				</AppButton>,
				<AppButton
					data-action={ CTA_CONFIRM }
					key="1"
					onClick={ onGuideRequestClose }
					isPrimary
				>
					{ __( 'Got it', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
			className="gla-campaign-creation-success-guide"
			onRequestClose={ handleRequestClose }
		>
			<div className="gla-campaign-creation-success-guide__header-image">
				<img
					alt={ __(
						'Drawing of a person who successfully launched a campaign',
						'google-listings-and-ads'
					) }
					height="160"
					src={ headerImageURL }
					width="413"
				/>
			</div>
			<GuidePageContent
				title={ __(
					`You've set up a Performance Max Campaign!`,
					'google-listings-and-ads'
				) }
			>
				{ createInterpolateElement(
					__(
						'You can pause or edit your campaign at any time. For best results, we recommend allowing your campaign to run for at least 14 days without pausing or editing. <link>Learn more about Performance Max technology.</link>',
						'google-listings-and-ads'
					),
					{
						link: (
							<ContentLink
								context="campaign-creation-performance-max"
								href="https://support.google.com/google-ads/answer/10724817"
							/>
						),
					}
				) }
			</GuidePageContent>
		</AppModal>
	);
}
