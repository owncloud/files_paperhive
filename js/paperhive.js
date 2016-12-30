/**
 * @author Piotr Mrowczynski <piotr.mrowczynski@yahoo.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

var Files_PaperHive = {

	/**
	 * Setup on page load
	 */
	initialize: function() {
		$(document).bind('mouseup', this._onClickDocument);
	},

	/**
	 * Handles event when clicking outside editor
	 */
	_onClickDocument: function(event) {
		var menuItem = $(event.target).closest('.menuitem');
		if(menuItem.length && menuItem.attr('data-filetype') === 'paperhive') {
			OC.hideMenus();
			//TODO add logic here because someone clicked on paperhive
	   }
	},
};

Files_PaperHive.NewFileMenuPlugin = {

	attach: function(menu) {
		var fileList = menu.fileList;

		// only attach to main file list, public view is not supported yet
		if (fileList.id !== 'files') {
			return;
		}

		// register the new menu entry
		menu.addMenuEntry({
			id: 'paperhive',
			displayName: t('files_paperhive', 'PaperHive Book'),
			iconClass: 'icon-filetype-text',
			fileType: 'paperhive',
		});
	}
};

OCA.Files_PaperHive = Files_PaperHive;

OC.Plugins.register('OCA.Files.NewFileMenu', Files_PaperHive.NewFileMenuPlugin);

$(document).ready(function () {
	OCA.Files_PaperHive.initialize();
});
