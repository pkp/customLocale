- Custom Locale Plugin
- Version: (see version.xml)
- Author: Carola Fanselow (updates by Alec Smecher, Erik Hanson)

[![Build Status](https://travis-ci.org/pkp/customLocale.svg?branch=stable-3_3_0)](https://travis-ci.org/pkp/customLocale)

About
-----
This plugin allows to customize locales via the OMP GUI. The default locales
are replaced but not overwritten and can easily be restored. A simple
documentation can be printed that lists all changes made to the locales. The
locales can be customized in the tab 'locale' in management > settings >
website. There is a search function for files and for locale keys within the
files. The documentation can be printed in the plugin's settings section.

License
-------
Copyright (c) 2016-2020 Language Science Press

This plugin is licensed under the GNU General Public License v2. 

Upgrading From A Previous Version
---------------------------------
Starting with OJS/OMP 3.2, the locale file format has changed from XML to PO.
Existing users of this plugin MUST convert their files when migrating from
a release older than OJS/OMP 3.2 that uses this plugin.

This process is described at:
https://github.com/pkp/pkp-lib/issues/5169

System Requirements
-------------------
This plugin is compatible with OJS 3.3 and OMP 3.3.

Installation
------------
To install the plugin:
 - Upload the tar.gz file in OMP (Management > Website Settings > Plugins > Generic Plugins)

Support
---------------
A documentation on this plugin can be found in pluginDocumentation_customLocale.md


