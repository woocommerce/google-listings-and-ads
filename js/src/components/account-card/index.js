/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';
import GridiconPhone from 'gridicons/dist/phone';
import { Icon, store as storeIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import googleLogoURL from './gogole-g-logo.svg';
import googleMCLogoURL from './google-merchant-center-logo.svg';
import googleAdsLogoURL from './google-ads-logo.svg';
import wpLogoURL from './wp-logo.svg';
import './index.scss';

/**
 * Enum of account card appearances.
 *
 * @enum {string}
 */
export const APPEARANCE = {
	EMPTY: 'empty',
	WPCOM: 'wpcom',
	GOOGLE: 'google',
	GOOGLE_MERCHANT_CENTER: 'google_merchant_center',
	GOOGLE_ADS: 'google_ads',
	PHONE: 'phone',
	ADDRESS: 'address',
};

const googleLogo = (
	<img
		src={ googleLogoURL }
		alt={ __( 'Google Logo', 'google-listings-and-ads' ) }
		width="40"
		height="40"
	/>
);

const googleMCLogo = (
	<img
		src={ googleMCLogoURL }
		alt={ __( 'Google Merchant Center Logo', 'google-listings-and-ads' ) }
		width="40"
		height="40"
	/>
);

const googleAdsLogo = (
	<img
		src={ googleAdsLogoURL }
		alt={ __( 'Google Ads Logo', 'google-listings-and-ads' ) }
		width="40"
		height="40"
	/>
);

const wpLogo = (
	<img
		src={ wpLogoURL }
		alt={ __( 'WordPress.com Logo', 'google-listings-and-ads' ) }
		width="40"
		height="40"
	/>
);

const appearanceDict = {
	[ APPEARANCE.EMPTY ]: {},
	[ APPEARANCE.WPCOM ]: {
		icon: wpLogo,
		title: 'WordPress.com',
	},
	[ APPEARANCE.GOOGLE ]: {
		icon: googleLogo,
		title: __( 'Google', 'google-listings-and-ads' ),
	},
	[ APPEARANCE.GOOGLE_MERCHANT_CENTER ]: {
		icon: googleMCLogo,
		title: __( 'Google Merchant Center', 'google-listings-and-ads' ),
		description: __(
			'Required to sync products and list on Google',
			'google-listings-and-ads'
		),
	},
	[ APPEARANCE.GOOGLE_ADS ]: {
		icon: googleAdsLogo,
		title: __( 'Google Ads', 'google-listings-and-ads' ),
		description: __(
			'Connect with millions of shoppers who are searching for products like yours and drive sales with Google.',
			'google-listings-and-ads'
		),
	},
	[ APPEARANCE.PHONE ]: {
		icon: <GridiconPhone size={ 32 } />,
		title: __( 'Phone number', 'google-listings-and-ads' ),
	},
	[ APPEARANCE.ADDRESS ]: {
		icon: <Icon icon={ storeIcon } size={ 32 } />,
		title: __( 'Store address', 'google-listings-and-ads' ),
	},
};

// The `center` is the default alignment, and no need to append any additional class name.
const alignStyleName = {
	center: false,
	top: `gla-account-card__styled--align-top`,
};

/**
 * Renders a Card component with account info and status.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {boolean} [props.disabled=false] Whether display the Card in disabled style.
 * @param {APPEARANCE} [props.appearance=APPEARANCE.EMPTY]
 *   Kind of account to indicate the default card appearance, which could include icon, title, and description.
 *   If didn't specify this prop, all properties of appearance will be `undefined` (nothing shown),
 *   and it's usually used for full customization.
 * @param {JSX.Element} [props.icon] Card icon. It will fall back to the icon of respective `appearance` config if not specified.
 * @param {JSX.Element} [props.title] Card title. It will fall back to the title of respective `appearance` config if not specified.
 * @param {JSX.Element} [props.description] Description content below the card title. It will fall back to the description of respective `appearance` config if not specified.
 * @param {JSX.Element} [props.helper] Helper content below the card description.
 * @param {JSX.Element} [props.indicator] Indicator of actions or status on the right side of the card.
 * @param {'center'|'top'} [props.alignIcon='center'] Specify the vertical alignment of leading icon.
 * @param {'center'|'top'} [props.alignIndicator='center'] Specify the vertical alignment of `indicator`.
 * @param {Array<JSX.Element>} [props.children] Children to be rendered if needs more content within the card.
 * @param {Object} [props.restProps] Props to be forwarded to Section.Card.
 */
export default function AccountCard( {
	className,
	disabled = false,
	appearance = APPEARANCE.EMPTY,
	icon = appearanceDict[ appearance ].icon,
	title = appearanceDict[ appearance ].title,
	description = appearanceDict[ appearance ].description,
	helper,
	alignIcon = 'center',
	indicator,
	alignIndicator = 'center',
	children,
	...restProps
} ) {
	const cardClassName = classnames(
		'gla-account-card',
		disabled ? 'gla-account-card--is-disabled' : false,
		className
	);

	const iconClassName = classnames(
		'gla-account-card__icon',
		alignStyleName[ alignIcon ]
	);

	const indicatorClassName = classnames(
		'gla-account-card__indicator',
		alignStyleName[ alignIndicator ]
	);

	return (
		<Section.Card className={ cardClassName } { ...restProps }>
			<Section.Card.Body>
				<Flex gap={ 4 }>
					{ icon && (
						<FlexItem className={ iconClassName }>
							{ icon }
						</FlexItem>
					) }
					<FlexBlock>
						{ title && (
							<Subsection.Title className="gla-account-card__title">
								{ title }
							</Subsection.Title>
						) }
						{ description && (
							<div className="gla-account-card__description">
								{ description }
							</div>
						) }
						{ helper && (
							<div className="gla-account-card__helper">
								{ helper }
							</div>
						) }
					</FlexBlock>
					{ indicator && (
						<FlexItem className={ indicatorClassName }>
							{ indicator }
						</FlexItem>
					) }
				</Flex>
			</Section.Card.Body>
			{ children }
		</Section.Card>
	);
}
