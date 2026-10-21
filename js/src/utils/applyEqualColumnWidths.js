/**
 * Returns a copy of a DataViews view object with `layout.styles` set so that
 * every field in `fieldIds` receives an equal share of the table width.
 *
 * Background: DataViews v14 defaults to `width: 1%` (shrink-to-fit) for every
 * column except the last one, which gets `col-expand` and absorbs all remaining
 * space. Passing explicit widths via `view.layout.styles` overrides that
 * behaviour and distributes columns evenly.
 *
 * The divisor is `fieldIds.length + 1` to reserve a proportional share for any
 * column that is sized outside this list (e.g. the title/primary column or the
 * Actions column).
 *
 * @param {Object}   view     DataViews view object.
 * @param {string[]} fieldIds IDs of the fields to distribute evenly.
 * @return {Object} View with `layout.styles` applied.
 */
const applyEqualColumnWidths = ( view, fieldIds ) => {
	const columnWidth = `${ Math.floor( 100 / ( fieldIds.length + 1 ) ) }%`;
	return {
		...view,
		layout: {
			...( view.layout ?? {} ),
			styles: Object.fromEntries(
				fieldIds.map( ( id ) => [ id, { width: columnWidth } ] )
			),
		},
	};
};

export default applyEqualColumnWidths;
