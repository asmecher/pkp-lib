/**
 * @file js/controllers/grid/users/UserGridHandler.js
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserGridHandler
 * @ingroup js_controllers_grid
 *
 * @brief User grid handler. Used to keep user grids in sync, such as when
 *  merging users.
 */
(function($) {

	// Define the namespace.
	$.pkp.controllers.grid.users = $.pkp.controllers.grid.users || {};


	/**
	 * @constructor
	 *
	 * @extends $.pkp.controllers.grid.GridHandler
	 *
	 * @param {jQueryObject} $grid The grid this handler is
	 *  attached to.
	 * @param {Object} options Grid handler configuration.
	 */
	$.pkp.controllers.grid.users.UserGridHandler =
			function($grid, options) {
		this.parent($grid, options);

		this.bind('userMerged', function() {

			// Close the modal with the merging user grid
			// This is a little bit hacky. The `formSubmitted` event is
			// typically fired by an AjaxFormHandler when a form is submitted,
			// and AjaxModalHandler listens to it to close.
			this.trigger('formSubmitted');

			this.refreshGridHandler();
		});
	};
	$.pkp.classes.Helper.inherits($.pkp.controllers.grid.users.UserGridHandler,
			$.pkp.controllers.grid.GridHandler);


/** @param {jQuery} $ jQuery closure. */
}(jQuery));
