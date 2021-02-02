/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../../../../wcdl/section';
import TitleButtonLayout from '../title-button-layout';

const WordPressDotComAccount = () => {
	// TODO: call backend API upon clicking Connect button.
	const handleConnectClick = () => {};

	return (
		<Section
			title={ __( 'WordPress.com', 'google-listings-and-ads' ) }
			description={ __(
				'WooCommerce requires a WordPress.com account to connect to Google.',
				'google-listings-and-ads'
			) }
		>
			<Section.Card>
				<Section.Card.Body>
					<TitleButtonLayout
						title={ __(
							'Connect your WordPress.com account',
							'google-listings-and-ads'
						) }
						button={
							<Button isSecondary onClick={ handleConnectClick }>
								{ __( 'Connect', 'google-listings-and-ads' ) }
							</Button>
						}
					/>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default WordPressDotComAccount;
