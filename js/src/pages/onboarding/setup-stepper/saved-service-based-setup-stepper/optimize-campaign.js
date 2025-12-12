/**
 * Internal dependencies
 */
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import AssetGroup from '~/components/paid-ads/asset-group';

const OptimizeCampaign = ( { onSubmit } ) => {
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();

	return (
		<CampaignAssetsForm onSubmit={ onSubmit } countryCodes={ countryCodes }>
			<AssetGroup />
		</CampaignAssetsForm>
	);
};

export default OptimizeCampaign;
