/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../../../../wcdl/section';
import DisabledDiv from '../../../../components/disabled-div';
import TitleButtonLayout from '../title-button-layout';

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
						<TitleButtonLayout
							title={ __(
								'Connect your Google account',
								'google-listings-and-ads'
							) }
							button={
								<Button
									isSecondary
									disabled={ disabled }
									onClick={ handleConnectClick }
								>
									{ __(
										'Connect',
										'google-listings-and-ads'
									) }
								</Button>
							}
						/>
					</Section.Card.Body>
				</Section.Card>
			</Section>
		</DisabledDiv>
	);
};

export default GoogleAccount;
