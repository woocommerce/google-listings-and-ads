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
import ContentButtonLayout from '.~/components/content-button-layout';
import './index.scss';

const ReclaimUrlFailCard = ( props ) => {
	const { onRetry = () => {} } = props;

	const handleRetryClick = () => {
		onRetry();
	};

	return (
		<Section.Card className="gla-reclaim-url-fail-card">
			<Section.Card.Body>
				<ContentButtonLayout>
					<div>
						<Subsection.Title className="gla-reclaim-url-fail-card__title">
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
					<AppButton isSecondary onClick={ handleRetryClick }>
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
