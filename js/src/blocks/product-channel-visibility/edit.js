/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { SelectControl, Notice } from '@wordpress/components';
import { __experimentalUseProductEntityProp as useProductEntityProp } from '@woocommerce/product-editor';

/**
 * Internal dependencies
 */
import styles from './editor.module.scss';

/**
 * @typedef {import('../types.js').ProductEditorBlockContext} ProductEditorBlockContext
 */

/**
 * @typedef {Object} ProductChannelVisibilityAttributes
 * @property {string} property Property or metadata name in which the value is stored in the product data.
 * @property {import('@wordpress/components').SelectControl.Option} [options=[]] The options to be shown in the select field.
 * @property {string} valueOfSync The value of the "Sync and show" option to be used to determine the UI presentation.
 * @property {string} valueOfDontSync The value of the "Don't Sync and show" option to be used to determine the UI presentation.
 * @property {string} statusOfSynced The value of the "Synced" status to be used to determine the UI presentation.
 * @property {string} statusOfHasErrors The value of the "HasErrors" status to be used to determine the UI presentation.
 */

/**
 * Specific custom block for editing the channel visibility of the given product data.
 *
 * @param {Object} props React props.
 * @param {ProductChannelVisibilityAttributes} props.attributes
 * @param {ProductEditorBlockContext} props.context
 */
export default function Edit( { attributes, context } ) {
	const {
		valueOfSync: VALUE_SYNC,
		valueOfDontSync: VALUE_DONT_SYNC,
		statusOfSynced: STATUS_SYNCED,
		statusOfHasErrors: STATUS_HAS_ERRORS,
	} = attributes;

	const blockProps = useWooBlockProps( attributes );
	const [ value, setValue ] = useProductEntityProp( attributes.property, {
		postType: context.postType,
	} );

	const setVisibility = ( nextVisibility ) => {
		setValue( { ...value, channel_visibility: nextVisibility } );
	};

	const { is_visible: isVisible, sync_status: syncStatus, issues } = value;

	// Force to select the "Don't Sync and show" option if the product is invisible.
	const visibility = isVisible ? value.channel_visibility : VALUE_DONT_SYNC;

	let syncStatusText = null;

	if ( syncStatus === STATUS_HAS_ERRORS ) {
		syncStatusText = __( 'Issues detected', 'google-listings-and-ads' );
	} else if ( syncStatus ) {
		syncStatusText = syncStatus.replace( '-', ' ' );
	}

	const help = isVisible
		? ''
		: __(
				'This product cannot be shown on any channel because it is hidden from your store catalog. To enable this option, please change this product to be shown in the product catalog, and save the changes.',
				'google-listings-and-ads'
		  );

	const shouldDisplayNotice =
		isVisible &&
		syncStatusText !== null &&
		visibility === VALUE_SYNC &&
		syncStatus !== STATUS_SYNCED;

	const hasErrors = issues.length > 0;

	return (
		<div { ...blockProps }>
			<SelectControl
				disabled={ ! isVisible }
				options={ attributes.options }
				value={ visibility }
				onChange={ setVisibility }
				help={ help }
			/>
			{ shouldDisplayNotice && (
				<Notice
					className={ styles.notice }
					status={ hasErrors ? 'warning' : 'info' }
					isDismissible={ false }
				>
					<section>
						<h2>
							{ __(
								'Google sync status',
								'google-listings-and-ads'
							) }
						</h2>
						<p className={ styles.uppercaseFirstLetter }>
							{ syncStatusText }
						</p>
					</section>
					{ hasErrors && (
						<section>
							<h2>
								{ __( 'Issues', 'google-listings-and-ads' ) }
							</h2>
							<ul>
								{ issues.map( ( issue, idx ) => (
									<li key={ idx }>{ issue }</li>
								) ) }
							</ul>
						</section>
					) }
				</Notice>
			) }
		</div>
	);
}
