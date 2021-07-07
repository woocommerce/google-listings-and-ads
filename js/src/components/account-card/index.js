/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import { ReactComponent as GoogleLogo } from './gogole-g-logo.svg';
import './index.scss';

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
		Logo: GoogleLogo,
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
					<FlexItem>
						<Logo />
					</FlexItem>
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
