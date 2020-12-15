/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../../../../../wcdl/section';
import Subsection from '../../../../../wcdl/subsection';
import ContentButtonLayout from '../../content-button-layout';

const DisabledCard = () => {
	return (
		<Section.Card>
			<ContentButtonLayout>
				<Subsection.Title>
					{ __(
						'Connect your Merchant Center',
						'google-listings-and-ads'
					) }
				</Subsection.Title>
				<Button isSecondary disabled>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</Button>
			</ContentButtonLayout>
		</Section.Card>
	);
};

export default DisabledCard;
