[![Build Status](https://travis-ci.org/owncloud/files_paperhive.svg?branch=master)](https://travis-ci.org/owncloud/files_paperhive)

- [x] Support for 10.0

# Installation

To install, go to ```/apps``` in your ownCloud installation directory and ```git clone https://github.com/owncloud/files_paperhive```. In the apps admin panel enable PaperHive app.

Remember about the correct permissions for the www folder after cloning the repository, otherwise app might not work correctly! `https://doc.owncloud.org/server/10.0/admin_manual/installation/installation_wizard.html#strong-perms-label`

It is highly recommended, to place in each user root directory a default introduction PaperHive document, `https://paperhive.org/documents/items/ZYY0r21rJbqr` which will help the user orient in the new functionality!

# About ownCloud and PaperHive
Researchers, students, medicine and engineering specialists are among the core ownCloud users and ownCloud's team is committed to support them in the management and creation of research data and texts. To help users read, reference and discuss already published academic texts, ownCloud now integrates with PaperHive, a web platform for collaborative reading.

Researchers read 12-25 hours a week depending on their discipline. Yet, understanding research articles and books – some of the most complex documents in the world – is hard and inefficient in isolation. Students and inexperienced researchers waste time trying to decipher these texts alone, senior researchers dig through folders of articles irrelevant for their own work, and all at some point might repeat unknowingly others’ mistakes or include these as a citation in their own paper.

PaperHive looks at how the web platform could transform reading into a more social and active process of collaboration. It is a cross-publisher layer of interaction on top of published research documents that enables contextual and structured discussions in real time. PaperHive's main benefits for users:
* annotate and discuss published academic articles, books and textbooks
* ask and answer questions, help and benefit from the knowledge, opinions and results of your colleagues and the broad research community
* keep up-to-date with the newest developments around a specific research topic
* improve research texts by discovering and correcting mistakes publicly, make complex concepts more accessible
* share your thoughts and discoveries with the research community and increase your visibility as a researcher
* give structured feedback to your colleagues
* if you are a teacher, you can make lectures and seminars much more engaging for students

Close to 14 million academic articles and books can currently be read and discussed with PaperHive.

# Transforming Reading Into a Process of Collaboration

One of the greatest ownCloud features is sharing. Folders or files can be shared with groups, individual users or using password protected or public links.

![](https://github.com/mrow4a/files_paperhive/blob/master/screenshots/sharing_documents_1.png)

The PaperHive documents in this shared folder allow copyright-compliant sharing of research publications with groups of users. By using the `Discuss` button in the file list a user is quickly redirected to the PaperHive page. The `Discuss` button also shows the current number of discussions online. 
The user can now easily keep up-to-date with new developments around publications of interest and start public or private discussions to better understand or improve academic texts.

![](https://github.com/mrow4a/files_paperhive/blob/master/screenshots/sharing_documents_2.png)

Transform your lectures, research and engineering work into a process of collaboration with ownCloud and PaperHive!

# Add PaperHive document to ownCloud

The PaperHive plugin is shipped with the new file menu button "PaperHive Document", which allows adding the documents found on the PaperHive website at `https://paperhive.org`. These are no different to your regular `.doc` or `.jpg` files and will behave the same as any other files in the ownCloud user interface.

Clicking on the "PaperHive Document" button will ask you for a PaperHive URL or DocID, displaying helpful information in the yellow popup on top of your file list. You are free to choose submitting just DocID or whole URL!

![](https://github.com/mrow4a/files_paperhive/blob/master/screenshots/add_new_book_1.png)

DocID is an unique book identifier, which can be found in the URL of the document at `https://paperhive.org`, as shown in the example below, where URL is `https://paperhive.org/documents/items/ZYY0r21rJbqr` and unique DocID is `ZYY0r21rJbqr`.

![](https://github.com/mrow4a/files_paperhive/blob/master/screenshots/add_new_book_2.png)

Desired URL or DocID has to be inserted into the field below and confirmed pressing ENTER.

![](https://github.com/mrow4a/files_paperhive/blob/master/screenshots/add_new_book_3.png)

Your PaperHive Document is now in your synchronisation folder!

# Used PaperHive API

- [Document Items API v1.1.3 (/api/document-items/[ItemID])](https://github.com/paperhive/frontend/blob/v1.1.3/app/services/document-items-api.ts)
- [Discussion API v1.1.3 (/api/discussions?documentItem=[ItemID])](https://github.com/paperhive/frontend/blob/v1.1.3/app/components/document-item.ts)
