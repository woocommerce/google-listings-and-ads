/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import Section from '.~/wcdl/section';
import TitleButtonLayout from '../../title-button-layout';

const CreateAccountCard = () => {
	const { createMCAccount } = useAppDispatch();

	const handleCreateAccountClick = () => {
		createMCAccount();
	};

	return (
		<Section.Card>
			<Section.Card.Body>
				<TitleButtonLayout
					title={ __(
						'Create your Google Merchant Center account',
						'google-listings-and-ads'
					) }
					button={
						<Button
							isSecondary
							onClick={ handleCreateAccountClick }
						>
							{ __(
								'Create Account',
								'google-listings-and-ads'
							) }
						</Button>
					}
				></TitleButtonLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default CreateAccountCard;
