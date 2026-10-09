/**
 * Inserts an extension box into the shared channel visibility meta box at
 * its alphabetical position among the other boxes, by `data-service`.
 *
 * Every extension that renders into this meta box tags its top-level box with
 * `data-service` and places it with this function, so the boxes are always
 * in order and each new box only needs to find its own slot. A box that is
 * already in the container (e.g. server-rendered) is only moved when it is out
 * of place. Elements without `data-service` are ignored.
 *
 * @param {HTMLElement} container The meta box `.inside` element.
 * @param {HTMLElement} box The box to insert, with a `data-service` attribute.
 */
export const insertAlphabetically = ( container, box ) => {
	const services = Array.from( container.children ).filter(
		( element ) => element !== box && element.dataset.service
	);

	if ( ! services.length ) {
		if ( box.parentElement !== container ) {
			container.append( box );
		}
		return;
	}

	const next = services.find(
		( element ) =>
			element.dataset.service.localeCompare( box.dataset.service ) > 0
	);
	const reference = next ?? services[ services.length - 1 ].nextSibling;

	if ( reference === box || box.nextSibling === reference ) {
		return;
	}

	container.insertBefore( box, reference );
};
