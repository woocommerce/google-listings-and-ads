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

export default function NoContactInformationCard( {
	onEditClick,
	learnMoreUrl,
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
							'Google requires the phone number and store address for all stores using Google Merchant Center. This is required to verify that you are the owner of the business.',
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
