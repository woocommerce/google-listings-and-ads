/**
 * External dependencies
 */
import { useRef, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ASSET_FORM_KEY } from '~/constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import ValidationErrors from '~/components/validation-errors';
import AssetGroupTextSection from './asset-group-text-section';
import AssetGroupImagesSection from './asset-group-images-section';
import AssetGroupVideosSection from './asset-group-videos-section';
import './asset-group-editor.scss';

/**
 * Renders the UI panels for managing an asset group.
 *
 * Please note that this component relies on an CampaignAssetsForm's context and custom adapter,
 * so it expects a `CampaignAssetsForm` to existing in its parents.
 */
export default function AssetGroupEditor() {
	const firstErrorRef = useRef();
	const {
		values,
		adapter: { baseAssetGroup, validationRequestCount, assetGroupErrors },
	} = useAdaptiveFormContext();
	const finalUrl = baseAssetGroup[ ASSET_FORM_KEY.FINAL_URL ];
	const isSelectedFinalUrl = Boolean( finalUrl );

	firstErrorRef.current = null;

	useEffect( () => {
		if ( validationRequestCount > 0 && firstErrorRef.current ) {
			firstErrorRef.current.scrollIntoComponent();
		}
	}, [ validationRequestCount ] );

	function getNumOfIssues( key ) {
		if ( ! isSelectedFinalUrl || validationRequestCount === 0 ) {
			return 0;
		}

		const messages = assetGroupErrors[ key ];

		if ( Array.isArray( messages ) ) {
			return messages.length;
		}
		return messages ? 1 : 0;
	}

	function renderErrors( key ) {
		if ( getNumOfIssues( key ) === 0 ) {
			return null;
		}

		return <ValidationErrors messages={ assetGroupErrors[ key ] } />;
	}

	function refFirstErrorField( ref ) {
		if ( firstErrorRef.current || getNumOfIssues( this ) === 0 ) {
			return;
		}
		firstErrorRef.current = ref;
	}

	// Ideally, the initial data for `ImagesSelector` and `TextsEditor` should be `values` directly.
	// But the current WC's `Form` component can not properly set the multiple values synchronously.
	// Therefore, an additional `baseAssetGroup` is used to ensure that the updates of multiple values
	// can be performed simultaneously.
	//
	// There are three moments that need to ensure the initial data:
	// 1. After importing assets by a final URL, the asset values are set from the `AssetGroupSection`.
	//    - The fetched multiple values are set to `baseAssetGroup`.
	//    - The final URL in `baseAssetGroup` must be a valid value.
	// 2. After clearing the selected final URL, the asset values are set from the `AssetGroupSection`.
	//    - The default empty asset values are set to `baseAssetGroup`.
	//    - The final URL in `baseAssetGroup` must be null.
	// 3. When mounting this component, the asset values already synchronously exist in `values`.
	//    - The final URL in `baseAssetGroup` must be a valid value.
	//    - The value changes made by the user are only kept in `values`.
	//
	// Thus, when mounting, it needs to distinguish whether the final URL in `baseAssetGroup` is
	// already set, and it's not cleared afterward. If yes, it means the `values` should be used as
	// the initial data. Otherwise, the `baseAssetGroup` should be used.
	const isSelectedAssetGroupInitiallyRef = useRef( isSelectedFinalUrl );
	if ( ! isSelectedFinalUrl ) {
		isSelectedAssetGroupInitiallyRef.current = isSelectedFinalUrl;
	}
	const initialValues = isSelectedAssetGroupInitiallyRef.current
		? values
		: baseAssetGroup;

	return (
		<div className="gla-asset-group-editor" key={ finalUrl }>
			<AssetGroupTextSection
				finalUrl={ finalUrl }
				getNumOfIssues={ getNumOfIssues }
				initialValues={ initialValues }
				isSelectedFinalUrl={ isSelectedFinalUrl }
				refFirstErrorField={ refFirstErrorField }
				renderErrors={ renderErrors }
			/>

			<AssetGroupImagesSection
				getNumOfIssues={ getNumOfIssues }
				initialValues={ initialValues }
				isSelectedFinalUrl={ isSelectedFinalUrl }
				refFirstErrorField={ refFirstErrorField }
				renderErrors={ renderErrors }
			/>

			<AssetGroupVideosSection
				initialValues={ initialValues }
				isSelectedFinalUrl={ isSelectedFinalUrl }
			/>
		</div>
	);
}
