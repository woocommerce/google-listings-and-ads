/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconNotice from 'gridicons/dist/notice';

/**
 * Internal dependencies
 */
import Subsection from '.~/wcdl/subsection';
import Section from '.~/wcdl/section';
import AppButton from '.~/components/app-button';
import './no-contact-information-card.scss';

/**
 * Renders a warning section to prompt users to add contact information.
 *
 * @param {Object} props React props.
 * @param {Function} props.onEditClick Called when clicking on the "Add information" button.
 * @param {string} props.learnMoreUrl The URL of learn more link and the props `href` value of the link's click tracking event.
 * @param {string} props.learnMoreLinkId The props `link_id` value of the learn more link's click tracking event.
 */
export default function NoContactInformationCard( {
	onEditClick,
	learnMoreUrl,
	learnMoreLinkId,
} ) {
	return (
		<Section.Card className="gla-no-contact-information-card">
			<Section.Card.Body>
				<Subsection>
					<Subsection.Title>
						<div>
							<GridiconNotice />
						</div>
						{ __(
							'Please add your contact information',
							'google-listings-and-ads'
						) }
					</Subsection.Title>
					<Subsection.Body>
						{ __(
							'Google requires the phone number and store address for all stores using Google Merchant Center.',
							'google-listings-and-ads'
						) }
					</Subsection.Body>
				</Subsection>
				<Subsection>
					<Subsection.Body>
						<AppButton isPrimary onClick={ onEditClick }>
							{ __(
								'Add information',
								'google-listings-and-ads'
							) }
						</AppButton>
						<AppButton
							isTertiary
							target="_blank"
							eventName="gla_google_mc_link_click"
							eventProps={ {
								context:
									'settings-no-contact-information-notice',
								link_id: learnMoreLinkId,
								href: learnMoreUrl,
							} }
							href={ learnMoreUrl }
						>
							{ __( 'Learn more', 'google-listings-and-ads' ) }
						</AppButton>
					</Subsection.Body>
				</Subsection>
			</Section.Card.Body>
		</Section.Card>
	);
}
