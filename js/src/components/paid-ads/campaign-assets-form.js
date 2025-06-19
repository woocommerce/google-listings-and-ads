/**
 * External dependencies
 */
import { useState, useMemo } from '@wordpress/element';
import { isPlainObject } from 'lodash';

/**
 * Internal dependencies
 */
import { ASSET_GROUP_KEY, ASSET_FORM_KEY } from '~/constants';
import AdaptiveForm from '~/components/adaptive-form';
import AppSpinner from '~/components/app-spinner';
import validateCampaign from '~/components/paid-ads/validateCampaign';
import validateAssetGroup from '~/components/paid-ads/validateAssetGroup';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import useBudgetRecommendation from '~/hooks/useBudgetRecommendation';
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import { FILTER_BUDGET_RECOMMENDATIONS } from '~/utils/tracks';

/**
 * @typedef {import('~/components/types.js').CampaignFormValues} CampaignFormValues
 * @typedef {import('~/components/types.js').AssetGroupFormValues} AssetGroupFormValues
 * @typedef {import('~/data/types.js').AssetEntityGroup} AssetEntityGroup
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 */

const emptyAssetGroup = {
	[ ASSET_FORM_KEY.FINAL_URL ]: null,
	[ ASSET_FORM_KEY.BUSINESS_NAME ]: '',
	[ ASSET_FORM_KEY.MARKETING_IMAGE ]: [],
	[ ASSET_FORM_KEY.SQUARE_MARKETING_IMAGE ]: [],
	[ ASSET_FORM_KEY.PORTRAIT_MARKETING_IMAGE ]: [],
	[ ASSET_FORM_KEY.LOGO ]: [],
	[ ASSET_FORM_KEY.HEADLINE ]: [],
	[ ASSET_FORM_KEY.LONG_HEADLINE ]: [],
	[ ASSET_FORM_KEY.DESCRIPTION ]: [],
	[ ASSET_FORM_KEY.CALL_TO_ACTION_SELECTION ]: null,
	[ ASSET_FORM_KEY.DISPLAY_URL_PATH ]: [],
};

/**
 * Converts the asset entity group data to the assets form values.
 *
 * @param  {AssetEntityGroup} [assetEntityGroup={}] Asset entity group data to be converted.
 * @return {AssetGroupFormValues} Assets form values.
 */
function convertAssetEntityGroupToFormValues( assetEntityGroup = {} ) {
	const { assets = {} } = assetEntityGroup;
	const formValues = { ...emptyAssetGroup };

	Object.keys( emptyAssetGroup ).forEach( ( key ) => {
		if ( assetEntityGroup.hasOwnProperty( key ) ) {
			formValues[ key ] = assetEntityGroup[ key ];
		} else if ( assets.hasOwnProperty( key ) ) {
			const asset = assets[ key ];

			if ( Array.isArray( asset ) ) {
				formValues[ key ] = asset.map( ( { content } ) => content );
			} else {
				formValues[ key ] = asset.content;
			}
		}
	} );

	return formValues;
}

function injectDailyBudget( values, budgetRecommendation ) {
	return Object.defineProperty( values, 'dailyBudget', {
		enumerable: true,
		get() {
			if ( this.level === 'custom' ) {
				return this.amount;
			}
			return budgetRecommendation[ this.level ].dailyBudget;
		},
	} );
}

function resolveInitialCampaign(
	initialCampaign,
	defaultCampaign,
	budgetRecommendation
) {
	const values = {
		...defaultCampaign,
		...initialCampaign,
	};

	if ( values.level !== 'custom' && ! budgetRecommendation[ values.level ] ) {
		values.level = budgetRecommendation.recommended
			? 'recommended'
			: 'custom';
	}

	return injectDailyBudget( values, budgetRecommendation );
}

/**
 * Renders a form based on AdaptiveForm for managing campaign and assets.
 *
 * @augments AdaptiveForm
 * @param {Object} props React props.
 * @param {CampaignFormValues} props.initialCampaign Initial campaign values.
 * @param {AssetEntityGroup} [props.assetEntityGroup] The asset entity group to be used in initializing the form values for editing.
 * @param {Array<CountryCode>} props.countryCodes Country codes to fetch budget recommendations.
 */
export default function CampaignAssetsForm( {
	initialCampaign,
	assetEntityGroup,
	countryCodes,
	...adaptiveFormProps
} ) {
	const initialAssetGroup = useMemo( () => {
		return convertAssetEntityGroupToFormValues( assetEntityGroup );
	}, [ assetEntityGroup ] );

	const [ baseAssetGroup, setBaseAssetGroup ] = useState( initialAssetGroup );
	const [ hasImportedAssets, setHasImportedAssets ] = useState( false );
	const { formatAmount } = useAdsCurrency();
	const {
		data: budgetRecommendationData,
		recommendedDailyBudget,
		hasResolved,
	} = useBudgetRecommendation( countryCodes );

	const budgetRecommendation = budgetRecommendationData || {};

	useEventPropertiesFilter(
		FILTER_BUDGET_RECOMMENDATIONS,
		budgetRecommendation?.eventProps
	);

	if ( ! hasResolved ) {
		return <AppSpinner />;
	}

	const extendAdapter = ( formContext ) => {
		const assetGroupErrors = validateAssetGroup( formContext.values );
		const finalUrl = assetEntityGroup?.[ ASSET_GROUP_KEY.FINAL_URL ];

		return {
			countryCodes,
			budgetRecommendation,
			// Currently, the PMax Assets feature in this extension has functional limits, therefore,
			// it needs to distinguish whether the `assetEntityGroup` is "empty" or not in order to
			// provide different special business logic.
			isEmptyAssetEntityGroup: ! finalUrl,
			baseAssetGroup,
			assetGroupErrors,
			/*
			  In order to show a Tip in the UI when assets are imported we created the hasImportedAssets
			  property. When the Final URL changes resetAssetGroup is called with the new Asset Group,
			  We check if any of the assets has been populated and update this property based on that.
			*/
			hasImportedAssets,
			isValidAssetGroup: Object.keys( assetGroupErrors ).length === 0,
			resetAssetGroup( assetGroup ) {
				const nextAssetGroup = isPlainObject( assetGroup )
					? assetGroup
					: initialAssetGroup;
				let hasNonEmptyAssets = false;

				Object.keys( emptyAssetGroup ).forEach( ( key ) => {
					if ( assetGroup && assetGroup[ key ]?.length ) {
						hasNonEmptyAssets = true;
					}
					formContext.setValue( key, nextAssetGroup[ key ] );
				} );

				setHasImportedAssets( hasNonEmptyAssets );
				setBaseAssetGroup( nextAssetGroup );
				formContext.adapter.hideValidation();
			},
		};
	};

	const validateCampaignWithMinimumAmount = ( values ) => {
		return validateCampaign( values, {
			dailyBudget: recommendedDailyBudget,
			formatAmount,
		} );
	};

	const handleChange = function ( ...args ) {
		injectDailyBudget( args[ 1 ], budgetRecommendation );

		if ( adaptiveFormProps.onChange ) {
			return adaptiveFormProps.onChange.apply( this, args );
		}
	};

	return (
		<AdaptiveForm
			initialValues={ {
				...resolveInitialCampaign(
					initialCampaign,
					{
						level: 'recommended',
						amount: recommendedDailyBudget,
					},
					budgetRecommendation
				),
				...initialAssetGroup,
			} }
			validate={ validateCampaignWithMinimumAmount }
			extendAdapter={ extendAdapter }
			{ ...adaptiveFormProps }
			onChange={ handleChange }
		/>
	);
}
