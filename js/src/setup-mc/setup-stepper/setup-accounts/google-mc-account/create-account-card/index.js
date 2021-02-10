/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import TitleButtonLayout from '../../title-button-layout';
import CreateAccountButton from '../create-account-button';

const CreateAccountCard = () => {
	return (
		<Section.Card>
			<Section.Card.Body>
				<TitleButtonLayout
					title={ __(
						'Create your Google Merchant Center account',
						'google-listings-and-ads'
					) }
					button={ <CreateAccountButton /> }
				></TitleButtonLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default CreateAccountCard;
