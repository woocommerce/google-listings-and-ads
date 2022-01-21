/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useLayout from '.~/hooks/useLayout';
import TopBar from '.~/components/stepper/top-bar';
import HelpIconButton from '.~/components/help-icon-button';
import CreatePaidAdsCampaignForm from './create-paid-ads-campaign-form';

const dashboardURL = getNewPath( {}, '/google/dashboard', {} );

const CreatePaidAdsCampaign = () => {
	useLayout( 'full-content' );

	return (
		<>
			<TopBar
				title={ __(
					'Create your paid campaign',
					'google-listings-and-ads'
				) }
				helpButton={ <HelpIconButton eventContext="create-ads" /> }
				backHref={ dashboardURL }
			/>
			<CreatePaidAdsCampaignForm />
		</>
	);
};

export default CreatePaidAdsCampaign;
