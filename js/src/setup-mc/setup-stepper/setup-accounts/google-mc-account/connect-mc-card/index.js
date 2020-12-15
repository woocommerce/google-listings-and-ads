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

const CreateMCCard = () => {
	const handleConnectClick = () => {};

	return (
		<Section.Card>
			<Subsection.Title>
				{ __(
					'Connect your Merchant Center',
					'google-listings-and-ads'
				) }
			</Subsection.Title>
			<ContentButtonLayout>
				<select></select>
				<Button isSecondary onClick={ handleConnectClick }>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</Button>
			</ContentButtonLayout>
		</Section.Card>
	);
};

export default CreateMCCard;
