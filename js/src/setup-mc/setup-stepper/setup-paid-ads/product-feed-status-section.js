/**
 * External dependencies
 */
import { sprintf, __, _n } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppDocumentationLink from '.~/components/app-documentation-link';
import SyncIcon from '.~/components/sync-icon';
import AppTooltip from '.~/components/app-tooltip';
import useSyncableProductsCalculation from '.~/hooks/useSyncableProductsCalculation';
import './product-feed-status-section.scss';

function ProductQuantity( { quantity } ) {
	if ( ! Number.isInteger( quantity ) ) {
		return null;
	}

	const text = sprintf(
		// translators: %d: number of products will be synced to Google Merchant Center.
		_n( '%d product', '%d products', quantity, 'google-listings-and-ads' ),
		quantity
	);

	return (
		<>
			<span className="gla-product-feed-status-section__product-quantity-separator" />
			<AppTooltip
				className
				position="top center"
				text={ __(
					'You can manage and edit your product feed after this setup',
					'google-listings-and-ads'
				) }
			>
				{ text }
			</AppTooltip>
		</>
	);
}

/**
 * @fires gla_documentation_link_click with `{ context: 'setup-paid-ads', link_id: 'product-feed-status-learn-more', href: 'https://support.google.com/merchants/answer/7439882' }`
 */

/**
 * Renders a section layout to elaborate on how the product listings will be processed
 * and show the number of products will be synced to Google Merchant Center.
 */
export default function ProductFeedStatusSection() {
	const { retrieve, count } = useSyncableProductsCalculation();

	// Retrieve the result of calculation that was requested when entering the Get Started page.
	useEffect( () => {
		retrieve();
	}, [ retrieve ] );

	return (
		<Section
			className="gla-product-feed-status-section"
			title={ __( 'Product feed status', 'google-listings-and-ads' ) }
			description={
				<AppDocumentationLink
					context="setup-paid-ads"
					linkId="product-feed-status-learn-more"
					href="https://support.google.com/merchants/answer/7439882"
				>
					{ __( 'Learn more', 'google-listings-and-ads' ) }
				</AppDocumentationLink>
			}
		>
			<Section.Card>
				<Section.Card.Body>
					<Flex align="flex-start" gap={ 3 }>
						<FlexItem>
							<SyncIcon />
						</FlexItem>
						<FlexBlock>
							<Section.Card.Title>
								{ __(
									'Your product listings are being uploaded',
									'google-listings-and-ads'
								) }
								<ProductQuantity quantity={ count } />
							</Section.Card.Title>
							{ __(
								'Google will review your product listings within 3-5 days. Once approved, your products will automatically be live and searchable on Google. You’ll be notified if there are any product feed issues.',
								'google-listings-and-ads'
							) }
						</FlexBlock>
					</Flex>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
}
