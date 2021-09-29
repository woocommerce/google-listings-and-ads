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

/**
 * Renders a Card component with account info and status.
 *
 * ## CSS Parts:
 * `icon` - area with the icon fetched for respective appearence.
 * `title` - area with the title fetched for respective appearence.
 * `indicator` - indicator/action area
 * `description` - description area
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {APPEARANCE | {icon, title}} props.appearance Kind of account to indicate the card appearance, or a tuple with icon and title to be used.
 * @param {JSX.Element} props.description Content below the card title.
 * @param {boolean} [props.hideIcon=false] Whether hide the leading icon.
 * @param {JSX.Element} [props.indicator] Indicator of actions or status on the right side of the card.
 * @param {Array<JSX.Element>} [props.children] Children to be rendered if needs more content within the card.
 */
export default function AccountCard( {
	className,
	appearance,
	description,
	hideIcon = false,
	indicator,
	children,
} ) {
	const { icon, title } =
		typeof appearance === 'object'
			? appearance
			: appearanceDict[ appearance ];

	return (
		<Section.Card className={ classnames( 'gla-account-card', className ) }>
			<Section.Card.Body>
				<Flex gap={ 4 }>
					{ ! hideIcon && (
						<FlexItem className="part--icon">{ icon }</FlexItem>
					) }
					<FlexBlock>
						<Subsection.Title className="part--title">
							{ title }
						</Subsection.Title>
						<div className="part--description">{ description }</div>
					</FlexBlock>
					{ indicator && (
						<FlexItem className="part--indicator">
							{ indicator }
						</FlexItem>
					) }
				</Flex>
			</Section.Card.Body>
			{ children }
		</Section.Card>
	);
}
