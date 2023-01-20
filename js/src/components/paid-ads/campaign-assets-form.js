/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AdaptiveForm from '.~/components/adaptive-form';
import validateCampaign from '.~/components/paid-ads/validateCampaign';
import validateAssetGroup from '.~/components/paid-ads/validateAssetGroup';

/**
 * @typedef {import('.~/components/types.js').CampaignFormValues} CampaignFormValues
 * @typedef {import('.~/components/types.js').AssetGroupFormValues} AssetGroupFormValues
 */

const emptyAssetGroup = {
	final_url: null,
	business_name: null,
	marketing_image: [],
	square_marketing_image: [],
	logo: [],
	headline: [],
	long_headline: [],
	description: [],
	call_to_action_selection: null,
	display_url_path: [],
};

function handleValidate( values ) {
	return {
		...validateCampaign( values ),
		...validateAssetGroup( values ),
	};
}

/**
 * Renders a form based on AdaptiveForm for managing campaign and assets.
 *
 * @augments AdaptiveForm
 * @param {Object} props React props.
 * @param {CampaignFormValues} props.initialCampaign Initial campaign values.
 * @param {AssetGroupFormValues} [props.initialAssetGroup] Initial asset group values. It will be the internal `emptyAssetGroup` by default.
 */
export default function CampaignAssetsForm( {
	initialCampaign,
	initialAssetGroup = emptyAssetGroup,
	...adaptiveFormProps
} ) {
	const [ baseAssetGroup, setBaseAssetGroup ] = useState( initialAssetGroup );
	const [ validationRequestCount, setValidationRequestCount ] = useState( 0 );

	const extendAdapter = ( formContext ) => ( {
		baseAssetGroup,
		validationRequestCount,
		isValidCampaign:
			Object.keys( validateCampaign( formContext.values ) ).length === 0,
		resetAssetGroup( assetGroup ) {
			const nextAssetGroup = assetGroup || initialAssetGroup;

			Object.keys( emptyAssetGroup ).forEach( ( key ) => {
				formContext.setValue( key, nextAssetGroup[ key ] );
			} );
			setBaseAssetGroup( nextAssetGroup );
			setValidationRequestCount( 0 );
		},
		showValidation() {
			setValidationRequestCount( validationRequestCount + 1 );
		},
	} );

	return (
		<AdaptiveForm
			initialValues={ {
				...initialCampaign,
				...initialAssetGroup,
			} }
			validate={ handleValidate }
			extendAdapter={ extendAdapter }
			{ ...adaptiveFormProps }
		/>
	);
}
