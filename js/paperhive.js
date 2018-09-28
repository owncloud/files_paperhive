/**
 * @author Piotr Mrowczynski <piotr.mrowczynski@yahoo.com>
 *
 * @copyright Copyright (c) 2017, Piotr Mrowczynski.
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

/**
 *
 * global: OCA, OC, _
 *
 * */

var Files_PaperHive;
Files_PaperHive = {

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

    /**
     * Gets if is paperhive file
     */
    isPaperHive: function (fileName) {
        var parts = fileName.split('.');
        var extension = "";
        if (parts.length > 1) {
            extension = parts.pop();
        }

        return (extension === 'paperhive');

    },

    /**
     * Registers the file actions
     */
    registerFileActions: function () {
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
            render: function (actionSpec, isDefault, context) {
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
    initialize: function () {
        $(document).bind('mouseup', this._onClickDocument);
        this.$notification = null;
        this.registerFileActions();
    },

    createContainer: function () {
        var self = this;
        $.ajax({
            type: 'GET',
            url: OC.generateUrl('/apps/files_paperhive/ajax/getpaperhivedetails')
        })
            .done(function (phdata) {
                var containerString = '<div class="icon-paperhive"></div>' +
                    '<div><p class="normal">' + t('files_paperhive', 'Visit PaperHive at') + ' </p><p class="normal">' + phdata.paperhive_base_url + '</p><p class="normal"> ' + t('files_paperhive', 'and transform reading into a process of collaboration!') + '</p></div>' +
                    //'<div><span></span></div>' +
                    '<div><p class="bold">' + t('files_paperhive', 'Your DocID') + ' </p><p class="normal">' + t('files_paperhive', 'is the last fragment of PaperHive document URL.') + '</p></div>' +
                    '<div><p class="normal">' + t('files_paperhive', 'Exemplary URL') + ': </p><p class="bold">' + phdata.paperhive_base_url + phdata.paperhive_base_document_url + phdata.paperhive_bookid_example + '</p></div>';

                self.container = $('<div class="notification_paperhive"></div>').html(
                    containerString
                );
                self.containerOptions = {
                    isHTML: true,
                    timeout: 30
                };
                self.$notification = OC.Notification.showHtml(
                    self.container,
                    self.containerOptions
                );
            })
            .fail(function (message) {
                OC.dialogs.alert(t('files_paperhive', 'An error occurred!'));
            });
    },

    createNotification: function () {
        if (this.$notification === null) {
            this.createContainer();
        }
    },

    hideNotification: function () {
        if (!OC.Notification.isHidden() && this.$notification != null) {
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
    loadFile: function (dir, filename, fetchDiscussions, success, failure) {
        $.get(
            OC.generateUrl('/apps/files_paperhive/ajax/loadfile'),
            {
                filename: filename,
                dir: dir,
                fetchDiscussions: fetchDiscussions
            }
        ).done(function (fileContents) {
            // Call success callback
            success(fileContents);
        }).fail(function (jqXHR) {
            var message;

            try {
                message = JSON.parse(jqXHR.responseText).message;
            } catch (e) {
            }

            failure(message);
        });
    },

    validatePaperHiveJSON: function (paperHiveObject) {
        //validate request
        var successResponseMetaTags = ['authors', 'title'];
		var successResponseTags = ['id','metadata'];
        var errorResponseTags = ['status', 'message'];

        for (var responseErrorTagId in errorResponseTags) {
            if (paperHiveObject.hasOwnProperty(errorResponseTags[responseErrorTagId])) {
                return false;
            }
        }

		for (var responseSuccessTagId in successResponseTags) {
			if (!paperHiveObject.hasOwnProperty(successResponseTags[responseSuccessTagId])) {
				return false;
			}
		}

		for (var successResponseMetaTagId in successResponseMetaTags) {
			if (!paperHiveObject.metadata.hasOwnProperty(successResponseMetaTags[successResponseMetaTagId])) {
				return false;
			}
		}

        return true;
    },

    /**
     * Handles request for book contents to PaperHive API
     */
    getPaperHiveBook: function (dir, bookID, success, failure) {
        $.get(
            OC.generateUrl('/apps/files_paperhive/ajax/getpaperhivedocument'),
            {
                dir: dir,
                bookID: bookID
            }
        ).done(function (paperHiveData) {
            // Success - found valid Book at PaperHive
            success(paperHiveData);
        }).fail(function (jqXHR) {
            var message;

            try {
                message = JSON.parse(jqXHR.responseText).message;
            } catch (e) {
            }

            failure("Error occured while connecting to PaperHive: " + message);
        });

    },

    setDiscussionCount: function ($tr) {
        OCA.Files_PaperHive.loadFile(
            $tr.attr('data-path'),
            $tr.attr('data-file'),
            "true",
            function (paperHiveData) {
                var discussionsCount = paperHiveData.paperhive_discussion_count;
                OCA.Files_PaperHive._updatePaperHiveFileData($tr, discussionsCount);
            },
            function (message) {
            }
        );
    },

    _updatePaperHiveFileData: function ($tr, discussionCount) {
        $tr.find('.filename .thumbnail').css('background-image', 'url(' + OC.imagePath('files_paperhive', 'paperhive-icon') + ')');
        var action = $tr.find('.fileactions .action[data-action="ShowPaper"]');

        action.addClass('shared-style');
        var icon = action.find('.icon');

        var message = '';
        if (discussionCount === -1) {
            message = t('files_paperhive', 'Discuss');
        } else {
            message = t('files_paperhive', 'Discuss') + ' (' + discussionCount + ')';
        }
        action.html('<span> ' + message + '</span>').prepend(icon);
    },

    /**
     * Handles the FileAction click event
     */
    _onPaperHiveTrigger: function (filename, context) {
        // Get the file data
        this.loadFile(
            context.dir,
            filename,
            "false",
            function (paperHiveData) {
                try {
                    var paperHiveObject = JSON.parse(paperHiveData.paperhive_document);
                } catch (e) {
                    OC.dialogs.alert(t('files_paperhive', 'Your [.paperhive] file is not a valid PaperHive document, please redownload document'));
                    return;
                }

                if (!OCA.Files_PaperHive.validatePaperHiveJSON(paperHiveObject)) {
                    OC.dialogs.alert(t('files_paperhive', 'Your [.paperhive] file is not a valid PaperHive document, please redownload document'));
                    return;
                }

                var paperhiveUrl = paperHiveData.paperhive_base_url + paperHiveData.paperhive_base_document_url + paperHiveObject.id;

                var w = window.open(paperhiveUrl, '_blank');
                if (!w) {
                    window.location.href = paperhiveUrl;
                }
            },
            function (message) {
                // Oh dear
                OC.dialogs.alert(t('files_paperhive', 'An error occurred!'));
            }
        );
    },

    /**
     * Handles event when clicking outside editor
     */
    _onClickDocument: function (event) {
        var menuItem = $(event.target).closest('.menuitem');
        var notificationItem = $(event.target).closest('.notification_paperhive');

        if (menuItem.length && menuItem.attr('data-action') === 'paperhive') {
            OCA.Files_PaperHive.createNotification();
        } else if (!notificationItem.length) {
            OCA.Files_PaperHive.hideNotification();
        }
    }
};

Files_PaperHive.NewFileMenuPlugin = {

    attach: function (menu) {
        var fileList = menu.fileList;

        // only attach to main file list, public view is not supported yet
        if (fileList.id !== 'files') {
            return;
        }

        // register the new menu entry
        menu.addMenuEntry({
            id: 'paperhive',
            displayName: t('files_paperhive', 'PaperHive'),
            templateName: t('files_paperhive', 'DocID'),
            iconClass: 'icon-filetype-paperhive',
            fileType: 'file',
            actionHandler: function (bookURL) {
                // Hide any remaining notification
                OCA.Files_PaperHive.hideNotification();

                var bookArray = bookURL.split( '/' );
                var bookID = '';
                if (bookArray.length === 1) {
                    // User gave only ID
                    bookID = bookArray[0];
                } else if (bookArray.length > 0){
                    // User gave URL, extract ID
                    bookID = bookArray[bookArray.length-1];
                }


                // Get the file data from PaperHive API
                var dir = fileList.getCurrentDirectory();
                var $saveNot = OC.Notification.showHtml(t('files_paperhive', 'Saving...'));
                OCA.Files_PaperHive.getPaperHiveBook(
                    dir,
                    bookID,
                    function (paperHiveData) {
                        var filename = paperHiveData.filename+paperHiveData.extension;

                        fileList.filesClient.getFileInfo(
                            paperHiveData.path,
                            {
                                properties: fileList._getWebdavProperties()
                            })
                            .then(function(status, data) {
                                fileList.add(data, {scrollTo: true});
                                var $tr = fileList.findFileEl(filename);
                                OCA.Files_PaperHive._updatePaperHiveFileData($tr, paperHiveData.paperhive_discussion_count);
                                OC.Notification.hide($saveNot);
                            })
                            .fail(function(status) {
                                OC.Notification.hide($saveNot);
                                OCA.Files_PaperHive.failureNotification(t('files_paperhive', 'Please reload the page, error occured'));
                            });
                    },
                    function (message) {
                        OC.Notification.hide($saveNot);
                        OCA.Files_PaperHive.failureNotification(t('files_paperhive', 'Cannot add your PaperHive document with BookID {id}. {message}', {id: bookID, message: message}));
                    }
                );
            }
        });
    }
};

Files_PaperHive.FileMenuPlugin = {

    attach: function (fileList) {
        // use delegate to catch the case with multiple file lists
        fileList.$el.on('fileActionsReady', function (ev) {
            var $files = ev.$files;
            var $phfiles = [];

            _.each($files, function (file) {
                var $tr = $(file);

                if (OCA.Files_PaperHive.isPaperHive($tr.attr('data-file'))) {
                    OCA.Files_PaperHive._updatePaperHiveFileData($tr, -1);
                    $phfiles.push(file);
                }
            });

            setTimeout(function () {
                _.each($phfiles, function (file) {
                    var $tr = $(file);
                    OCA.Files_PaperHive.setDiscussionCount($tr);
                });
            }, 20);

        });
    }
};

OCA.Files_PaperHive = Files_PaperHive;

OC.Plugins.register('OCA.Files.NewFileMenu', Files_PaperHive.NewFileMenuPlugin);
OC.Plugins.register('OCA.Files.FileList', Files_PaperHive.FileMenuPlugin);

$(document).ready(function () {
    OCA.Files_PaperHive.initialize();
});
