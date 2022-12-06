/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useState } from '@wordpress/element';
import { ExternalLink } from 'extracted/@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import AssetsLoader from './assets-loader';
import './final-url-card.scss';

/**
 * @typedef {import('.~/data/types.js').AssetGroup} AssetGroup
 */

/**
 * Renders the Card UI for managing the final URL and getting the suggested assets.
 *
 * @param {Object} props React props.
 * @param {(assetGroup: AssetGroup | null) => void} props.onAssetsChange Callback function when the suggested assets are changed or reset to `null`.
 * @param {string} [props.initialFinalUrl] The initial final URL.
 * @param {boolean} [props.hideFooter=false] Whether to hide the card footer.
 */
export default function FinalUrlCard( {
	onAssetsChange,
	initialFinalUrl,
	hideFooter = false,
} ) {
	const [ finalUrl, setFinalUrl ] = useState( initialFinalUrl || null );

	const description = finalUrl ? (
		<ExternalLink href={ finalUrl }>{ finalUrl }</ExternalLink>
	) : (
		__(
			'Choose a page that people reach after clicking your ad. This might be your homepage, or a more specific page.',
			'google-listings-and-ads'
		)
	);

	const handleAssetsLoaded = ( assetGroup ) => {
		setFinalUrl( assetGroup.final_url );
		onAssetsChange( assetGroup );
	};

	const handleReselectClick = () => {
		setFinalUrl( null );
		onAssetsChange( null );
	};

	const className = classnames( {
		'gla-final-url-card': true,
		'gla-final-url-card--has-selected-url': finalUrl,
	} );

	return (
		<AccountCard
			className={ className }
			appearance={ APPEARANCE.FINAL_URL }
			alignIcon="top"
			description={ description }
		>
			<Section.Card.Footer align="end" gap={ 4 } hidden={ hideFooter }>
				{ finalUrl ? (
					<AppButton
						isTertiary
						text={ __(
							'Or, select another page',
							'google-listings-and-ads'
						) }
						onClick={ handleReselectClick }
					/>
				) : (
					<AssetsLoader onAssetsLoaded={ handleAssetsLoaded } />
				) }
			</Section.Card.Footer>
		</AccountCard>
	);
}
