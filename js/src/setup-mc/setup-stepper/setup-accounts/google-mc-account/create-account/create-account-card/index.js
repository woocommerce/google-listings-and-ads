/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import TitleButtonLayout from '../../../title-button-layout';
import CreateAccountButton from './create-account-button';
import AppTextButton from '.~/components/app-text-button';

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
					<AppTextButton isSecondary onClick={ onShowExisting }>
						{ __(
							'Or, use your existing Google Merchant Center account',
							'google-listings-and-ads'
						) }
					</AppTextButton>
				</Section.Card.Footer>
			) }
		</Section.Card>
	);
};

export default CreateAccountCard;
