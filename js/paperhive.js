/**
 * @author Piotr Mrowczynski <piotr.mrowczynski@yahoo.com>
 *
 * @copyright Copyright (c) 2017, ownCloud, Inc.
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
	 * Paperhive base URL
	 * TODO: Temporarly hardcore the paperhive base URL
	 */
	paperhive_base_url: 'https://paperhive.org',

	/**
	 * Paperhive url for document API
	 */
	paperhive_api_url: '/api/documents/',

	/**
	 * Paperhive url for text API
	 */
	paperhive_document_url: '/documents/',

	/**
	 * Paperhive url for discussions API
	 */
	paperhive_discussion_api_endpoint: '/discussions',


	/**
	 * Paperhive file extension
	 */
	paperhive_file_extension: '.paperhive',

	/**
	 * Holds the notification container
	 */
	$notification: null,

	/**
	 * Holds the notification html
	 */
	container: null,

	/**
	 * Holds the notification html options
	 */
	containerOptions: null,

	defaultAction: null,

	/**
	 * Gets if is paperhive file
	 */
	isPaperHive: function (fileName) {
		var parts=fileName.split('.');
		var extension = "";
		if (parts.length > 1) {
			extension=parts.pop();
		}

		if (extension === 'paperhive') {
			return true;
		}
		return false;
	},

	/**
	 * Registers the file actions
	 */
	registerFileActions: function() {
		var mimetype = 'application/octet-stream';

		OCA.Files.fileActions.registerAction({
			name: 'ShowPaper',
			displayName: '',
			altText: t('core', 'Show Paper'),
			mime: mimetype,
			actionHandler: _.bind(this._onPaperHiveTrigger, this),
			permissions: OC.PERMISSION_READ,
			iconClass: 'icon-filetype-paperhive',
			type: OCA.Files.FileActions.TYPE_INLINE,
			render: function(actionSpec, isDefault, context) {
				if (OCA.Files_PaperHive.isPaperHive(context.$file.attr('data-file'))) {
					return OCA.Files.fileActions._defaultRenderAction.call(OCA.Files.fileActions, actionSpec, isDefault, context);
				}
				// don't render anything
				return null;
			}

		});
	},

	/**
	 * Setup on page load
	 */
	initialize: function() {
		$(document).bind('mouseup', this._onClickDocument);
		this.container = $('<div class="notification_paperhive"></div>').html(
			'<div class="icon-paperhive"></div>' +
			'<div><p class="normal">Visit PaperHive at </p><p class="normal">' + OCA.Files_PaperHive.paperhive_base_url + '</p><p class="normal"> and transform reading into a process of collaboration!</p></div>' +
			//'<div><span></span></div>' +
			'<div><p class="bold">Your Book ID </p><p class="normal">is the last fragment of PaperHive document URL.</p></div>' +
			'<div><p class="normal">Example: </p><p class="normal">' + OCA.Files_PaperHive.paperhive_base_url + OCA.Files_PaperHive.paperhive_document_url + '</p><p class="bold">Ra5WnkxImoOE</p></div>'
		);
		this.containerOptions = {
			isHTML: true,
			timeout: 30
		};
		this.$notification = null;
		this.registerFileActions();
	},

	createNotification: function() {
		if (this.$notification === null){
			this.$notification = OC.Notification.showHtml(
				this.container,
				this.containerOptions
			);
		}
	},

	hideNotification: function() {
		if (!OC.Notification.isHidden() && this.$notification != null){
			OC.Notification.hide(this.$notification);
			this.$notification = null;
		}
	},

	failureNotification: function (message) {
		OC.dialogs.alert(message, t('files_paperhive', 'An error occurred!'));
	},

	/**
	 * Loads the data through AJAX
	 */
	loadFile: function(dir, filename, success, failure) {
		$.get(
			OC.generateUrl('/apps/files_paperhive/ajax/loadfile'),
			{
				filename: filename,
				dir: dir
			}
		).done(function(fileContents) {
			// Call success callback
			success(fileContents);
		}).fail(function(jqXHR) {
			var message;

			try{
				message = JSON.parse(jqXHR.responseText).message;
			}catch(e){
			}

			failure(message);
		});
	},


	/**
	 * Send the new file data back to the server
	 */
	saveFile: function(data, path, success, failure) {
		$.ajax({
			type: 'PUT',
			url: OC.generateUrl('/apps/files_paperhive/ajax/savefile'),
			data: {
				filecontents: data,
				path: path
			}
		})
			.done(success)
			.fail(function(jqXHR) {
				var message;

				try{
					message = JSON.parse(jqXHR.responseText).message;
				}catch(e){
				}

				failure(message);
			});
	},

	validatePaperHiveJSON: function (paperHiveObject) {
		//validate request
		var successResponseTags = ['id', 'authors','title','publisher'];
		var errorResponseTags = ['status','message'];
		for (var responseTag in errorResponseTags) {
			if( paperHiveObject.hasOwnProperty(errorResponseTags[responseTag]) ) {
				return false;
			}
		}

		for (var responseTag in successResponseTags) {
			if( !paperHiveObject.hasOwnProperty(successResponseTags[responseTag]) ) {
				return false;
			}
		}

		return true;
	},

	/**
	 * Handles request for book contents to PaperHive API
	 */
	getPaperHiveBook: function(bookID, success, failure) {
		var paperhiveUrl = OCA.Files_PaperHive.paperhive_base_url + OCA.Files_PaperHive.paperhive_api_url + bookID;

		// TODO: remember to implement AJAX request to paperHive, not only mimic the response
		var paperHiveString =
			'{' +
			'"status": "404",' +
			'"message": "document not found",' +
			'}';
		if (bookID === 'Ra5WnkxImoOE') {
			paperHiveString =
				'{' +
				'"id": "Ra5WnkxImoOE",' +
				'"revision": "M4HIAWe7NbAs",' +
				'"remote": {' +
				'"type": "oapen",' +
				'"id": "605035"' +
				'},' +
				'"title": "Borderland City in New India",' +
				'"authors": [' +
				'{' +
				'"name": "Duncan McDuie-Ra"' +
				'}' +
				'],' +
				'"publishedAt": "2016-01-01T14:00:00.000Z",' +
				'"abstract": "Borderland Cities in New India explores contemporary urban life in two cities in India’s Northeast borderland at a time of dramatic change. Social and economic transformation from India’s embrace of neoliberalism and globalisation, often referred to as ‘new’ India, has become a popular subject for academic analysis in the last decade. This is epitomised by focus on so-called ‘mega-cities’, reflecting a general trend in scholarship on other parts of Asia. However, far less attention has been afforded to borderland regions and to the provincial cities of ‘new’ India. Using ethnographic material, this book focuses on two cities in India’s Northeast borderland: Aizawl and Imphal. Both cities have been profoundly affected by armed conflict, militarism, displacement, and inter-ethnic tensions. Yet, both are also experiencing intensified flows of goods and people, rapid urban development, and expansion of Indian and foreign capital associated with the opening of the borderland west to the rest of India and east to the rest of Asia.",' +
				'"isbn": "9789048525362",' +
				'"tags": [],' +
				'"publisher": "Amsterdam University Press",' +
				'"distributor": "Knowledge Unlatched",' +
				'"isOpenAccess": true,' +
				'"file": {' +
				'"url": "http://oapen.org/download?type=document&docid=605035",' +
				'"hasCors": false' +
				'}' +
				'}';
		}

		// try {
		// 	xhr = new XMLHttpRequest();
		//
		// 	xhr.onreadystatechange = function () {
		// 		if (xhr.readyState === 4) {
		// 			if (xhr.status === 200) { // Success
		// 				OC.dialogs.alert('GET', t('files_paperhive', 'Got data'));
		// 			} else { // Failure, fallback to regular notice
		// 				OC.dialogs.alert('GET', t('files_paperhive', 'An error occurred!'));
		// 			}
		// 		}
		// 	};
		// 	xhr.open('GET', paperhiveUrl, true);
		// 	xhr.send();
		// } catch (e) {
		// 	failure("PaperHive cannot connect to requested address: " + paperhiveUrl);
		// 	return;
		// }

		try {
			var paperHiveObject = JSON.parse(paperHiveString);
		} catch (e) {
			failure("PaperHive cannot be found at requested address: " + paperhiveUrl);
			return;
		}

		if (bookID != 'Ra5WnkxImoOEe' && bookID != 'Ra5WnkxImoOE') {
			failure("Failed requesting PaperHive at address: " + paperhiveUrl);
		} else {
			if (!OCA.Files_PaperHive.validatePaperHiveJSON(paperHiveObject)){
				failure("Requested PaperHive document cannot be found at address: " + paperhiveUrl);
				return;
			}

			// Success - found valid Book at PaperHive
			//var filename = paperHiveObject.title + OCA.Files_PaperHive.$paperhive_file_extension;
			var filename = paperHiveObject.title + OCA.Files_PaperHive.paperhive_file_extension;
			success(filename, paperHiveString);
		}

	},

	_updatePaperHiveFileData: function($tr) {
		if (OCA.Files_PaperHive.isPaperHive($tr.attr('data-file'))) {
			$tr.find('.filename .thumbnail').css('background-image', 'url(' + OC.imagePath('files_paperhive', 'paperhive-icon') + ')');
			var action = $tr.find('.fileactions .action[data-action="ShowPaper"]');
			action.addClass('shared-style');
			var icon = action.find('.icon');

			var discussionCount = Math.floor(Math.random() * (20 - 0 + 1)) + 0;

			var message = t('files_paperhive', 'Discuss') + ' (' + discussionCount + ')';
			action.html('<span> ' + message + '</span>').prepend(icon);
		}
	},

	/**
	 * Handles the FileAction click event
	 */
	_onPaperHiveTrigger: function(filename, context) {
		// Get the file data
		this.loadFile(
			context.dir,
			filename,
			function(paperHiveString) {
				try {
					var paperHiveObject = JSON.parse(paperHiveString);
				} catch (e) {
					failure("Your [.paperhive] file is not a valid PaperHive document, please redownload document");
					return;
				}

				if (!OCA.Files_PaperHive.validatePaperHiveJSON(paperHiveObject)){
					failure("Your [.paperhive] file is not a valid PaperHive document, please redownload document");
					return;
				}

				var paperhiveUrl = OCA.Files_PaperHive.paperhive_base_url + OCA.Files_PaperHive.paperhive_document_url + paperHiveObject.id;

				// TODO: this should open new window, not redirect
				// $.ajax({
				// 	url:      paperhiveUrl,
				// 	async:    false,
				// 	dataType: "json",
				// 	success:  function() {
				// 		window.open(paperhiveUrl);
				// 	}
				// });
				window.location = paperhiveUrl;
			},
			function(message){
				// Oh dear
				OC.dialogs.alert(message, t('files_paperhive', 'An error occurred!'));
			});
	},

	/**
	 * Handles event when clicking outside editor
	 */
	_onClickDocument: function(event) {
		var menuItem = $(event.target).closest('.menuitem');
		var notificationItem = $(event.target).closest('.notification_paperhive');

		if(menuItem.length && menuItem.attr('data-action') === 'paperhive') {
			OCA.Files_PaperHive.createNotification();
	    } else if (!notificationItem.length){
			OCA.Files_PaperHive.hideNotification();
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
			templateName: t('files_paperhive', 'Your Book ID'),
			iconClass: 'icon-filetype-paperhive',
			fileType: 'file',
			actionHandler: function(bookID) {
				// Hide any remaining notification
				OCA.Files_PaperHive.hideNotification();

				// Get the file data from PaperHive API
				OCA.Files_PaperHive.getPaperHiveBook(
					bookID,
					function(fileName, paperHiveString) {
						var dir = fileList.getCurrentDirectory();

						if (dir == '/') {
							var path = dir + fileName;
						} else {
							var path = dir + '/' + fileName;
						}
						// Try to save
						OCA.Files_PaperHive.saveFile(
							paperHiveString,
							path,
							function (data) {
								fileList.addAndFetchFileInfo(path, '', {scrollTo: true}).then(
									function(status, data) {
										var $tr = fileList.findFileEl(fileName);
										OCA.Files_PaperHive._updatePaperHiveFileData($tr);
									},
									function() {
										OCA.Files_PaperHive.failureNotification('Could not create file');
									}
								);
							},
							function (message) {
								OCA.Files_PaperHive.failureNotification(message);
							}
						);
					},
					function(message){
						OCA.Files_PaperHive.failureNotification(message);
					}
				);
			}
		});
	}
};

Files_PaperHive.FileMenuPlugin = {

	attach: function(fileList) {
		// use delegate to catch the case with multiple file lists
		fileList.$el.on('fileActionsReady', function(ev){
			var $files = ev.$files;

			_.each($files, function(file) {
				var $tr = $(file);
				OCA.Files_PaperHive._updatePaperHiveFileData($tr);
			});
		});
	}
};

OCA.Files_PaperHive = Files_PaperHive;

OC.Plugins.register('OCA.Files.NewFileMenu', Files_PaperHive.NewFileMenuPlugin);
OC.Plugins.register('OCA.Files.FileList',Files_PaperHive.FileMenuPlugin);

$(document).ready(function () {
	OCA.Files_PaperHive.initialize();
});
