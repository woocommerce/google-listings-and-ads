/**
 * External dependencies
 */
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AccountCard from '.~/components/account-card';
import './connect-account-card.scss';

/**
 * ConnectAccountCard component renders an account card with a title, helper text, body, and footer sections.
 *
 * @param {Object} props Props.
 * @param {string} props.title The title of the account card.
 * @param {string} props.helperText The helper text for the account card.
 * @param {JSX.Element} props.body The content for the body of the account card.
 * @param {JSX.Element} props.footer The content for the footer of the account card.
 * @param {string} [props.className] Additional class names for the account card.
 * @return {JSX.Element} ConnectAccountCard component.
 */
const ConnectAccountCard = ( {
	title,
	helperText,
	body,
	footer,
	className,
} ) => {
	return (
		<AccountCard
			className={ classNames(
				'gla-google-combo-service-account-card',
				className
			) }
			title={ title }
			helper={ helperText }
		>
			<Section.Card.Body className="gla-google-combo-service-account-card__body">
				{ body }
			</Section.Card.Body>
			<Section.Card.Footer className="gla-google-combo-service-account-card__footer">
				{ footer }
			</Section.Card.Footer>
		</AccountCard>
	);
};

export default ConnectAccountCard;
