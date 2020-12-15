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

const CreateAccountCard = () => {
	const handleCreateAccountClick = () => {};

	return (
		<Section.Card>
			<ContentButtonLayout>
				<Subsection.Title>
					{ __(
						'Create your Google Merchant Center account',
						'google-listings-and-ads'
					) }
				</Subsection.Title>
				<Button isSecondary onClick={ handleCreateAccountClick }>
					{ __( 'Create Account', 'google-listings-and-ads' ) }
				</Button>
			</ContentButtonLayout>
		</Section.Card>
	);
};

export default CreateAccountCard;
