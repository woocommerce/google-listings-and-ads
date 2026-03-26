/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '~/components/app-documentation-link';
import AppButton from '~/components/app-button';
import AppModal from '~/components/app-modal';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import './modal.scss';

export const CONTEXT = 'eu-political-declaration-modal';
const CAMPAIGNS_BASE_URL = 'https://ads.google.com/aw/campaigns';

/**
 * @typedef {Object} Campaign
 * @property {number} id The unique identifier for the campaign.
 * @property {string} name The name of the campaign.
 */

/**
 * Modal component for EU Political Declaration. Displays a list of campaigns missing the declaration.
 *
 * @fires gla_documentation_link_click with `{ context: 'eu-political-declaration-modal', link_id: 'eu-political-content', href: 'https://support.google.com/adspolicy/answer/6014595' }`
 *
 * @param {Object} props The component props.
 * @param {Campaign[]} props.campaigns An array of campaign objects that are missing the EU political declaration.
 * @param {Function} props.onRequestClose A callback function to be called when the modal is requested to be closed.
 *
 * @return {JSX.Element} The rendered Modal component.
 */
const Modal = ( { campaigns, onRequestClose } ) => {
	const { googleAdsAccount, hasFinishedResolution } = useGoogleAdsAccount();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	const params = googleAdsAccount?.ocid
		? { ocid: googleAdsAccount.ocid }
		: {};
	const campaignsUrl = addQueryArgs( CAMPAIGNS_BASE_URL, params );

	return (
		<AppModal
			title={ __(
				'Action required: EU political ads declaration',
				'google-listings-and-ads'
			) }
			buttons={ [
				<AppButton
					key="go-to-google-ads"
					variant="primary"
					href={ campaignsUrl }
					eventName="gla_eu_political_declaration_modal_go_to_google_ads_click"
					eventProps={ { context: CONTEXT } }
				>
					{ __( 'Go to Google Ads', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
			className="gla-eu-political-declaration-modal"
		>
			<p>
				{ createInterpolateElement(
					__(
						"Your Google Ads campaigns are missing the required EU political ads declaration. You'll need to complete this in Google Ads before you can create or edit campaigns here. <link>Learn about political ads</link>",
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								href="https://support.google.com/adspolicy/answer/6014595"
								linkId="eu-political-content"
								context={ CONTEXT }
							/>
						),
					}
				) }
			</p>

			<Notice
				status="warning"
				isDismissible={ false }
				className="gla-eu-political-declaration-modal__notice--warning"
			>
				{ __(
					"After April 1, 2026, you won't be able to create or edit campaigns without completing this declaration.",
					'google-listings-and-ads'
				) }
			</Notice>

			<ul>
				{ campaigns.map( ( { id, name } ) => (
					<li key={ id }>{ name }</li>
				) ) }
			</ul>
		</AppModal>
	);
};

export default Modal;
