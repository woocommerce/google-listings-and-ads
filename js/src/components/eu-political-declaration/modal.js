/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { Icon } from '@wordpress/components';
import { external as externalIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AppModal from '~/components/app-modal';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import './modal.scss';

const CAMPAIGNS_BASE_URL = 'https://ads.google.com/aw/campaigns';

/**
 * @event gla_eu_political_declaration_modal_go_to_google_ads_click
 * @property {string} context The context in which the modal was closed, set to the value of the `eventContext` prop.
 */

/**
 * Modal component for EU Political Declaration. Displays a list of campaigns missing the declaration.
 *
 * @fires gla_eu_political_declaration_modal_go_to_google_ads_click with `{ context: 'dashboard'|'edit-ads'|'create-ads'|'setup-ads'|'setup-mc' }`
 *
 * @param {Object} props The component props.
 * @param {Function} props.onRequestClose A callback function to be called when the modal is requested to be closed.
 * @param {string} props.eventContext The context in which the component is rendered, used for tracking purposes.
 *
 * @return {JSX.Element} The rendered Modal component.
 */
const Modal = ( { onRequestClose, eventContext } ) => {
	const { googleAdsAccount, hasFinishedResolution } = useGoogleAdsAccount();

	if ( ! hasFinishedResolution ) {
		return null;
	}

	const params = googleAdsAccount?.ocid
		? { ocid: googleAdsAccount.ocid }
		: {};
	const campaignsUrl = addQueryArgs( CAMPAIGNS_BASE_URL, params );

	const isEditOrDashboard =
		eventContext === 'edit-ads' || eventContext === 'dashboard';

	const description = isEditOrDashboard
		? __(
				'To comply with EU political ads rules, you can’t create or edit campaigns in this account until the required declarations are added.',
				'google-listings-and-ads'
		  )
		: __(
				"You can't edit your campaigns until you complete the EU political ads declaration in Google Ads.",
				'google-listings-and-ads'
		  );

	return (
		<AppModal
			title={ __(
				'Missing EU ads declaration',
				'google-listings-and-ads'
			) }
			buttons={ [
				<AppButton
					key="go-to-google-ads"
					variant="primary"
					href={ campaignsUrl }
					target="_blank"
					eventName="gla_eu_political_declaration_modal_go_to_google_ads_click"
					eventProps={ { context: eventContext } }
					icon={ <Icon icon={ externalIcon } /> }
					iconPosition="right"
					iconSize={ 16 }
				>
					{ __( 'Go to Google Ads', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
			className="gla-eu-political-declaration-modal"
			size="small"
		>
			<p>{ description }</p>
		</AppModal>
	);
};

export default Modal;
