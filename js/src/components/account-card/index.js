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
import './index.scss';

/**
 * Enum of account card appearances.
 *
 * @enum {string}
 */
export const APPEARANCE = {
	GOOGLE: 'google',
	PHONE: 'phone',
	ADDRESS: 'address',
};

const appearanceDict = {
	[ APPEARANCE.GOOGLE ]: {
		icon: (
			<img
				src={ googleLogoURL }
				alt={ __( 'Google Logo', 'google-listings-and-ads' ) }
				width="40"
				height="40"
			/>
		),
		title: __( 'Google account', 'google-listings-and-ads' ),
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
const iconStyleName = {
	center: false,
	top: `gla-account-card__icon--align-top`,
};

/**
 * Renders a Card component with account info and status.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {APPEARANCE | {icon, title}} props.appearance Kind of account to indicate the card appearance, or a tuple with icon and title to be used.
 * @param {boolean} [props.disabled=false] Whether display the Card in disabled style.
 * @param {JSX.Element} props.description Content below the card title.
 * @param {boolean} [props.hideIcon=false] Whether hide the leading icon.
 * @param {'center'|'top'} [props.alignIcon='center'] Specify the vertical alignment of leading icon.
 * @param {JSX.Element} [props.indicator] Indicator of actions or status on the right side of the card.
 * @param {Array<JSX.Element>} [props.children] Children to be rendered if needs more content within the card.
 */
export default function AccountCard( {
	className,
	appearance,
	disabled = false,
	description,
	hideIcon = false,
	alignIcon = 'center',
	indicator,
	children,
} ) {
	const { icon, title } =
		typeof appearance === 'object'
			? appearance
			: appearanceDict[ appearance ];

	const cardClassName = classnames(
		'gla-account-card',
		disabled ? 'gla-account-card--is-disabled' : false,
		className
	);

	const iconClassName = classnames(
		'gla-account-card__icon',
		iconStyleName[ alignIcon ]
	);

	return (
		<Section.Card className={ cardClassName }>
			<Section.Card.Body>
				<Flex gap={ 4 }>
					{ ! hideIcon && (
						<FlexItem className={ iconClassName }>
							{ icon }
						</FlexItem>
					) }
					<FlexBlock>
						<Subsection.Title className="gla-account-card__title">
							{ title }
						</Subsection.Title>
						<div className="gla-account-card__description">
							{ description }
						</div>
					</FlexBlock>
					{ indicator && (
						<FlexItem className="gla-account-card__indicator">
							{ indicator }
						</FlexItem>
					) }
				</Flex>
			</Section.Card.Body>
			{ children }
		</Section.Card>
	);
}
