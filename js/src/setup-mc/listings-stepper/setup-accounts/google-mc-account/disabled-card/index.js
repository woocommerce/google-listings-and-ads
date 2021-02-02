/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../../../../../wcdl/section';
import TitleButtonLayout from '../../../../../components/title-button-layout';

const DisabledCard = () => {
	return (
		<Section.Card>
			<Section.Card.Body>
				<TitleButtonLayout
					title={ __(
						'Connect your Merchant Center',
						'google-listings-and-ads'
					) }
					button={
						<Button isSecondary disabled>
							{ __( 'Connect', 'google-listings-and-ads' ) }
						</Button>
					}
				/>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default DisabledCard;
