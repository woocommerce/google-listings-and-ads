/**
 * External dependencies
 */
import GridiconPlusSmall from 'gridicons/dist/plus-small';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import './add-asset-item-button.scss';

export default function AddAssetItemButton( props ) {
	return (
		<AppButton
			className="gla-add-asset-item-button"
			isLink
			icon={ <GridiconPlusSmall /> }
			iconSize={ 16 }
			{ ...props }
		/>
	);
}
