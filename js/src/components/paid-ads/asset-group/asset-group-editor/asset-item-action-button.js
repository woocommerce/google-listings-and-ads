/**
 * External dependencies
 */
import GridiconPlusSmall from 'gridicons/dist/plus-small';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AIIcon from '~/images/ai-icon.svg?inline';
import './asset-item-action-button.scss';

export const ACTION_TYPES = {
	ADD: 'add',
	GENERATE: 'generate',
};

const ACTION_ICONS = {
	[ ACTION_TYPES.ADD ]: <GridiconPlusSmall />,
	[ ACTION_TYPES.GENERATE ]: <AIIcon />,
};

export default function AssetItemActionButton( {
	action = ACTION_TYPES.ADD,
	loading,
	...props
} ) {
	return (
		<AppButton
			className="gla-asset-item-action-button"
			icon={ ! loading ? ACTION_ICONS[ action ] : null }
			iconSize={ 16 }
			loading={ loading }
			isLink
			{ ...props }
		/>
	);
}
