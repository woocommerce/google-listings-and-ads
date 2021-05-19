/**
 * This file was cloned from @wordpress/components 12.0.8
 * https://github.com/WordPress/gutenberg/blob/%40wordpress/components%4012.0.8/packages/components/src/guide/index.js
 *
 * To meet the requirement of
 * https://github.com/woocommerce/google-listings-and-ads/issues/555
 */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Modal, KeyboardShortcuts, Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import PageControl from './page-control';
import FinishButton from './finish-button';

export default function Guide( {
	className,
	contentLabel,
	finishButtonText,
	onFinish,
	pages = [],
} ) {
	const [ currentPage, setCurrentPage ] = useState( 0 );

	const canGoBack = currentPage > 0;
	const canGoForward = currentPage < pages.length - 1;

	const goBack = () => {
		if ( canGoBack ) {
			setCurrentPage( currentPage - 1 );
		}
	};

	const goForward = () => {
		if ( canGoForward ) {
			setCurrentPage( currentPage + 1 );
		}
	};

	if ( pages.length === 0 ) {
		return null;
	}

	return (
		<Modal
			className={ classnames( 'components-guide', className ) }
			contentLabel={ contentLabel }
			onRequestClose={ onFinish }
		>
			<KeyboardShortcuts
				key={ currentPage }
				shortcuts={ {
					left: goBack,
					right: goForward,
				} }
			/>

			<div className="components-guide__container">
				<div className="components-guide__page">
					{ pages[ currentPage ].image }

					<PageControl
						currentPage={ currentPage }
						numberOfPages={ pages.length }
						setCurrentPage={ setCurrentPage }
					/>

					{ pages[ currentPage ].content }

					{ ! canGoForward && (
						<FinishButton
							className="components-guide__inline-finish-button"
							onClick={ onFinish }
						>
							{ finishButtonText || __( 'Finish' ) }
						</FinishButton>
					) }
				</div>

				<div className="components-guide__footer">
					{ canGoBack && (
						<Button
							className="components-guide__back-button"
							onClick={ goBack }
						>
							{ __( 'Previous' ) }
						</Button>
					) }
					{ canGoForward && (
						<Button
							className="components-guide__forward-button"
							onClick={ goForward }
						>
							{ __( 'Next' ) }
						</Button>
					) }
					{ ! canGoForward && (
						<FinishButton
							className="components-guide__finish-button"
							onClick={ onFinish }
						>
							{ finishButtonText || __( 'Finish' ) }
						</FinishButton>
					) }
				</div>
			</div>
		</Modal>
	);
}
