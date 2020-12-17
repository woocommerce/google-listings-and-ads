/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../../../../wcdl/section';
import Subsection from '../../../../wcdl/subsection';
import DisabledDiv from '../../../../components/disabled-div';
import ContentButtonLayout from '../content-button-layout';

const GoogleAccount = ( props ) => {
	const { disabled = false } = props;

	// TODO: call backend API upon clicking Connect button.
	const handleConnectClick = () => {};

	return (
		<DisabledDiv disabled={ disabled }>
			<Section
				title={ __( 'Google account', 'google-listings-and-ads' ) }
				description={ __(
					'WooCommerce uses your Google account to sync with Google Merchant Center and Google Ads.',
					'google-listings-and-ads'
				) }
			>
				<Section.Card>
					<Section.Card.Body>
						<ContentButtonLayout>
							<Subsection.Title>
								{ __(
									'Connect your Google account',
									'google-listings-and-ads'
								) }
							</Subsection.Title>
							<Button
								isSecondary
								disabled={ disabled }
								onClick={ handleConnectClick }
							>
								{ __( 'Connect', 'google-listings-and-ads' ) }
							</Button>
						</ContentButtonLayout>
					</Section.Card.Body>
				</Section.Card>
			</Section>
		</DisabledDiv>
	);
};

export default GoogleAccount;
