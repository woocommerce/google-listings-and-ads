/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '~/components/app-documentation-link';
import AppModal from '~/components/app-modal';
import Badge from '~/components/badge';
import ErrorIcon from '~/components/error-icon';
import WarningIcon from '~/components/warning-icon';

const SOURCE_LABEL = {
	merchant_center: __( 'Merchant Center', 'google-listings-and-ads' ),
	sync: __( 'Sync error', 'google-listings-and-ads' ),
};

const SEVERITY = {
	warning: {
		icon: <WarningIcon />,
		intent: 'warning',
		label: __( 'Warning', 'google-listings-and-ads' ),
	},
	error: {
		icon: <ErrorIcon />,
		intent: 'error',
		label: __( 'Error', 'google-listings-and-ads' ),
	},
};

/**
 * Lists one product's issues, from Merchant Center and from syncing, each with
 * its severity, what to do and a link to learn more.
 *
 * @fires gla_documentation_link_click with `{ context: 'product-feed-issues-modal', link_id: issue.code, href: issue.action_url }`
 *
 * @param {Object} props
 * @param {Object} props.product Product from the product feed response.
 * @param {Function} props.onRequestClose Closes the modal.
 */
const ProductIssuesModal = ( { product, onRequestClose } ) => (
	<AppModal
		className="gla-product-issues-modal"
		title={ product.title }
		onRequestClose={ onRequestClose }
	>
		<ul className="gla-product-issues-modal__list">
			{ product.issues.map( ( issue, index ) => {
				const severity =
					issue.severity === 'warning'
						? SEVERITY.warning
						: SEVERITY.error;

				return (
					<li
						key={ `${ issue.source }-${ issue.code }-${ index }` }
						className="gla-product-issues-modal__issue"
					>
						{ severity.icon }
						<div className="gla-product-issues-modal__details">
							<p className="gla-product-issues-modal__title">
								<strong>{ issue.issue }</strong>
								<Badge intent={ severity.intent }>
									{ severity.label }
								</Badge>
								{ SOURCE_LABEL[ issue.source ] && (
									<Badge>
										{ SOURCE_LABEL[ issue.source ] }
									</Badge>
								) }
							</p>
							{ issue.action && <p>{ issue.action }</p> }
							{ issue.action_url && (
								<AppDocumentationLink
									context="product-feed-issues-modal"
									linkId={ issue.code }
									href={ issue.action_url }
								>
									{ __(
										'Learn more',
										'google-listings-and-ads'
									) }
								</AppDocumentationLink>
							) }
						</div>
					</li>
				);
			} ) }
		</ul>
	</AppModal>
);

export default ProductIssuesModal;
