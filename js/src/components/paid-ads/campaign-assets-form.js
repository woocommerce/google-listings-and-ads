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
import useRaiseBudgetRecommendations from '~/hooks/useRaiseBudgetRecommendations';
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import { useAppDispatch } from '~/data';
import { FILTER_BUDGET_RECOMMENDATIONS } from '~/utils/tracks';
import round from '~/utils/round';

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
	[ ASSET_FORM_KEY.YOUTUBE_VIDEO ]: [],
};

const REQUIRED_TEXT_ASSET_KEYS = [
	ASSET_FORM_KEY.LONG_HEADLINE,
	ASSET_FORM_KEY.HEADLINE,
	ASSET_FORM_KEY.DESCRIPTION,
];

const REQUIRED_MEDIA_ASSET_KEYS = [
	ASSET_FORM_KEY.MARKETING_IMAGE,
	ASSET_FORM_KEY.SQUARE_MARKETING_IMAGE,
	ASSET_FORM_KEY.PORTRAIT_MARKETING_IMAGE,
];

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

			if ( this.level === 'current' ) {
				return this.currentAmount;
			}

			return budgetRecommendation[ this.level ].dailyBudget;
		},
	} );
}

function injectUpliftData( budgetRecommendation ) {
	const currentConversionsValue =
		budgetRecommendation?.current?.metrics?.conversionsValue;

	if ( ! currentConversionsValue ) {
		return budgetRecommendation;
	}

	const validLevelKeys = [ 'high', 'recommended', 'low' ];

	validLevelKeys.forEach( ( level ) => {
		// Check if budget recommendation and base budget recommendation have valid levels and `conversionsValue` is present within the metrics.
		if ( budgetRecommendation?.[ level ]?.metrics?.conversionsValue ) {
			const newConversionsValue =
				budgetRecommendation[ level ].metrics.conversionsValue;

			Object.defineProperty(
				budgetRecommendation[ level ].metrics,
				'uplift',
				{
					enumerable: true,
					value:
						currentConversionsValue > 0
							? round(
									( ( newConversionsValue -
										currentConversionsValue ) /
										currentConversionsValue ) *
										100
							  )
							: null,
				}
			);
		}
	} );

	return budgetRecommendation;
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
		values.level =
			budgetRecommendation.recommended && values.level !== 'current'
				? 'recommended'
				: 'current';
	}

	return injectDailyBudget( values, budgetRecommendation );
}

function hasValidAIGeneratedAssets( assetKeys, data ) {
	if ( ! data || typeof data !== 'object' ) {
		return false;
	}

	// Ensure object isn't empty
	if ( Object.keys( data ).length === 0 ) {
		return false;
	}

	// Ensure required keys exist + contain at least 1 non-empty string
	return assetKeys.every( ( key ) => {
		const value = data[ key ];

		return (
			Array.isArray( value ) &&
			value.length > 0 &&
			value.some(
				( item ) => typeof item === 'string' && item.trim().length > 0
			)
		);
	} );
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
	const { fetchGenAIMediaAssets, fetchGenAITextAssets } = useAppDispatch();
	const [ isFetchingGenAIAssets, setIsFetchingGenAIAssets ] =
		useState( false );
	const initialAssetGroup = useMemo( () => {
		return convertAssetEntityGroupToFormValues( assetEntityGroup );
	}, [ assetEntityGroup ] );

	const [ baseAssetGroup, setBaseAssetGroup ] = useState( initialAssetGroup );
	const [ hasImportedAssets, setHasImportedAssets ] = useState( false );
	const [ hasAISuggestedTextAssets, setHasAISuggestedTextAssets ] =
		useState( false );
	const [ hasAISuggestedMediaAssets, setHasAISuggestedMediaAssets ] =
		useState( false );
	const { formatAmount } = useAdsCurrency();
	const { data: budgetRecommendationData, hasResolved } =
		useBudgetRecommendation( countryCodes );

	const budgetRecommendation = budgetRecommendationData || {};

	useEventPropertiesFilter(
		FILTER_BUDGET_RECOMMENDATIONS,
		budgetRecommendation?.eventProps
	);

	// Check if campaign is being edited and get its budget recommendations.
	const campaignId = initialCampaign?.id;
	const isEditing = Boolean( campaignId );
	const {
		campaigns: raiseBudgetRecommendations,
		hasFinishedResolution: hasResolvedRaiseBudgetRecommendations,
	} = useRaiseBudgetRecommendations( campaignId );

	if ( ! hasResolved || ! hasResolvedRaiseBudgetRecommendations ) {
		return <AppSpinner />;
	}

	const selectedBudgetRecommendation =
		isEditing && raiseBudgetRecommendations.length
			? injectUpliftData( raiseBudgetRecommendations[ 0 ] )
			: budgetRecommendation;

	const extendAdapter = ( formContext ) => {
		const assetGroupErrors = validateAssetGroup( formContext.values );
		const finalUrl = assetEntityGroup?.[ ASSET_GROUP_KEY.FINAL_URL ];

		return {
			countryCodes,
			budgetRecommendation: selectedBudgetRecommendation,
			isEditing,
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

				const updatedContextValues = Object.fromEntries(
					Object.keys( emptyAssetGroup ).map( ( key ) => {
						if ( assetGroup?.[ key ]?.length ) {
							hasNonEmptyAssets = true;
						}
						return [ key, nextAssetGroup[ key ] ];
					} )
				);

				if ( Object.keys( updatedContextValues ).length ) {
					formContext.setValues( updatedContextValues );
				}

				setHasImportedAssets( hasNonEmptyAssets );
				setBaseAssetGroup( nextAssetGroup );
				setHasAISuggestedTextAssets( false );
				setHasAISuggestedMediaAssets( false );
				formContext.adapter.hideValidation();
			},
			isFetchingGenAIAssets,
			hasAISuggestedTextAssets,
			hasAISuggestedMediaAssets,
			async fetchGenAIAssets() {
				try {
					setIsFetchingGenAIAssets( true );
					const { data: textAssetsData } =
						await fetchGenAITextAssets( finalUrl );
					const { data: mediaAssetsData } =
						await fetchGenAIMediaAssets( finalUrl );

					const hasSuggestedTextAssets = hasValidAIGeneratedAssets(
						REQUIRED_TEXT_ASSET_KEYS,
						textAssetsData
					);

					const hasSuggestedMediaAssets = hasValidAIGeneratedAssets(
						REQUIRED_MEDIA_ASSET_KEYS,
						mediaAssetsData
					);

					setHasAISuggestedTextAssets( hasSuggestedTextAssets );
					setHasAISuggestedMediaAssets( hasSuggestedMediaAssets );
				} finally {
					setIsFetchingGenAIAssets( false );
				}
			},
		};
	};

	const validateCampaignWithMinimumAmount = ( values ) => {
		return validateCampaign( values, {
			dailyBudget: budgetRecommendation.dailyBudgetBaseline,
			formatAmount,
		} );
	};

	const handleChange = function ( ...args ) {
		injectDailyBudget( args[ 1 ], selectedBudgetRecommendation );

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
						amount: selectedBudgetRecommendation.recommendedDailyBudget,
					},
					selectedBudgetRecommendation,
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
