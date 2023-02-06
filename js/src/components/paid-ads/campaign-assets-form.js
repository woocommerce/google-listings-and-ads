/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ASSET_FORM_KEY } from '.~/constants';
import AdaptiveForm from '.~/components/adaptive-form';
import validateCampaign from '.~/components/paid-ads/validateCampaign';
import validateAssetGroup from '.~/components/paid-ads/validateAssetGroup';

/**
 * @typedef {import('.~/components/types.js').CampaignFormValues} CampaignFormValues
 * @typedef {import('.~/components/types.js').AssetGroupFormValues} AssetGroupFormValues
 */

const emptyAssetGroup = {
	[ ASSET_FORM_KEY.FINAL_URL ]: null,
	[ ASSET_FORM_KEY.BUSINESS_NAME ]: null,
	[ ASSET_FORM_KEY.MARKETING_IMAGE ]: [],
	[ ASSET_FORM_KEY.SQUARE_MARKETING_IMAGE ]: [],
	[ ASSET_FORM_KEY.LOGO ]: [],
	[ ASSET_FORM_KEY.HEADLINE ]: [],
	[ ASSET_FORM_KEY.LONG_HEADLINE ]: [],
	[ ASSET_FORM_KEY.DESCRIPTION ]: [],
	[ ASSET_FORM_KEY.CALL_TO_ACTION_SELECTION ]: null,
	[ ASSET_FORM_KEY.DISPLAY_URL_PATH ]: [],
};

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

	const extendAdapter = ( formContext ) => {
		const assetGroupErrors = validateAssetGroup( formContext.values );

		return {
			baseAssetGroup,
			validationRequestCount,
			assetGroupErrors,
			isValidAssetGroup: Object.keys( assetGroupErrors ).length === 0,
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
		};
	};

	return (
		<AdaptiveForm
			initialValues={ {
				...initialCampaign,
				...initialAssetGroup,
			} }
			validate={ validateCampaign }
			extendAdapter={ extendAdapter }
			{ ...adaptiveFormProps }
		/>
	);
}
