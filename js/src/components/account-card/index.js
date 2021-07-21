/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import './index.scss';

/**
 * Full URL to the Google G logo image.
 * Preferably we would inline it into HTML to reduce the bundle size.
 *
 * Unfortunately, React does not support `import.meta`, so we need to hardcode the module path.
 */
const googleLogoURL =
	glaData.assetsURL + 'js/src/components/account-card/gogole-g-logo.svg';

/**
 * Enum of account card appearances.
 *
 * @enum {string}
 */
export const APPEARANCE = {
	GOOGLE: 'google',
};

const appearanceDict = {
	[ APPEARANCE.GOOGLE ]: {
		Logo: (
			<img
				src={ googleLogoURL }
				alt={ __( 'Google Logo', 'google-listings-and-ads' ) }
				width="40"
				height="40"
			/>
		),
		title: __( 'Google account', 'google-listings-and-ads' ),
	},
};

/**
 * Renders a Card component with account info and status.
 *
 * @param {Object} props React props.
 * @param {APPEARANCE} props.appearance Kind of account to indicate the card appearance.
 * @param {JSX.Element} props.description Content below the card title.
 * @param {JSX.Element} [props.indicator] Indicator of actions or status on the right side of the card.
 * @param {Array<JSX.Element>} [props.children] Children to be rendered if needs more content within the card.
 */
export default function AccountCard( {
	appearance,
	description,
	indicator,
	children,
} ) {
	const { Logo, title } = appearanceDict[ appearance ];

	return (
		<Section.Card className="gla-account-card">
			<Section.Card.Body>
				<Flex gap={ 4 }>
					<FlexItem>{ Logo }</FlexItem>
					<FlexBlock>
						<Subsection.Title>{ title }</Subsection.Title>
						<div>{ description }</div>
					</FlexBlock>
					{ indicator && <FlexItem>{ indicator }</FlexItem> }
				</Flex>
			</Section.Card.Body>
			{ children }
		</Section.Card>
	);
}
