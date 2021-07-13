/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import TitleButtonLayout from '.~/components/title-button-layout';
import CreateAccountButton from './create-account-button';

const CreateAccountCard = ( props ) => {
	const {
		allowShowExisting,
		onShowExisting = () => {},
		onCreateAccount,
	} = props;

	return (
		<Section.Card>
			<Section.Card.Body>
				<TitleButtonLayout
					title={ __(
						'Create your Google Merchant Center account',
						'google-listings-and-ads'
					) }
					button={
						<CreateAccountButton
							onCreateAccount={ onCreateAccount }
						/>
					}
				></TitleButtonLayout>
			</Section.Card.Body>
			{ allowShowExisting && (
				<Section.Card.Footer>
					<Button isLink onClick={ onShowExisting }>
						{ __(
							'Or, use your existing Google Merchant Center account',
							'google-listings-and-ads'
						) }
					</Button>
				</Section.Card.Footer>
			) }
		</Section.Card>
	);
};

export default CreateAccountCard;
