/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '.~/components/app-documentation-link';
import AppButton from '.~/components/app-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import ContentButtonLayout from '../content-button-layout';

const ReclaimUrlFailCard = ( props ) => {
	const { onRetry = () => {} } = props;

	return (
		<Section.Card>
			<Section.Card.Body>
				<ContentButtonLayout>
					<div>
						<Subsection.Title>
							{ __(
								'We were not able to reclaim this URL.',
								'google-listings-and-ads'
							) }
						</Subsection.Title>
						<Subsection.HelperText>
							{ __(
								'You may not have permission to reclaim this URL, or an error might have occurred. Try again later or contact your Google account administrator.',
								'google-listings-and-ads'
							) }
						</Subsection.HelperText>
					</div>
					<AppButton isSecondary onClick={ onRetry }>
						{ __( 'Try again', 'google-listings-and-ads' ) }
					</AppButton>
				</ContentButtonLayout>
			</Section.Card.Body>
			<Section.Card.Footer>
				<AppDocumentationLink
					context="setup-mc"
					linkId="claim-url"
					href="https://support.google.com/merchants/answer/176793"
				>
					{ __(
						'Read more about claiming URLs',
						'google-listings-and-ads'
					) }
				</AppDocumentationLink>
			</Section.Card.Footer>
		</Section.Card>
	);
};

export default ReclaimUrlFailCard;
