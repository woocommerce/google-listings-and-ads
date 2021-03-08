/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import DisabledDiv from '.~/components/disabled-div';
import CardContent from './card-content';

const GoogleAccount = ( props ) => {
	const { disabled = false } = props;

	return (
		<DisabledDiv disabled={ disabled }>
			<Section
				title={ __( 'Google account', 'google-listings-and-ads' ) }
				description={
					<p>
						{ __(
							'WooCommerce uses your Google account to sync with Google Merchant Center and Google Ads.',
							'google-listings-and-ads'
						) }
					</p>
				}
			>
				<Section.Card>
					<Section.Card.Body>
						<CardContent disabled={ disabled } />
					</Section.Card.Body>
				</Section.Card>
			</Section>
		</DisabledDiv>
	);
};

export default GoogleAccount;
