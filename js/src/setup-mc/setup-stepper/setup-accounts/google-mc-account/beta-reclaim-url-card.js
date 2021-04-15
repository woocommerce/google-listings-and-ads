/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '.~/components/app-documentation-link';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import ContentButtonLayout from '.~/components/content-button-layout';
import betaExistingProductListingsStatement from './betaExistingProductListingsStatement';

/**
 * This is used temporarily for beta testing purpose. For production roll out, we should remove this and use the above ReclaimUrlCard instead.
 *
 * @param {Object} props Props.
 */
const BetaReclaimUrlCard = ( props ) => {
	const { websiteUrl } = props;

	return (
		<Section.Card>
			<Section.Card.Body>
				<ContentButtonLayout>
					<div>
						<Subsection.Title>
							{ createInterpolateElement(
								__(
									'Your URL, <url />, is currently claimed by another Merchant Center account. ',
									'google-listings-and-ads'
								),
								{
									url: <span>{ websiteUrl }</span>,
								}
							) }
						</Subsection.Title>
						<Subsection.HelperText>
							{ betaExistingProductListingsStatement }
						</Subsection.HelperText>
					</div>
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

export default BetaReclaimUrlCard;
