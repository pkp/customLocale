- Custom Locale Plugin
- Version: 1.1.0.1
- Author: Carola Fanselow (updates by Alec Smecher, Erik Hanson)

About
-----
This plugin allows to customize locales via the OJS/OMP/OPS GUI. The default locales
are replaced but not overwritten and can easily be restored. A simple
documentation can be printed that lists all changes made to the locales. The
locales can be customized in the tab 'locale' in management > settings >
website. There is a search function for files and for locale keys within the
files. The documentation can be printed in the plugin's settings section.

License
-------
Copyright (c) 2016-2021 Language Science Press

This plugin is licensed under the GNU General Public License v2. 

Upgrading From A Previous Version
---------------------------------
Starting with OJS/OMP/OPS 3.2, the locale file format has changed from XML to PO.
Existing users of this plugin MUST convert their files when migrating from
a release older than OJS/OMP/OPS 3.2 that uses this plugin.

This process is described at:
https://github.com/pkp/pkp-lib/issues/5169

System Requirements
-------------------
This plugin is compatible with OJS/OMP/OPS 3.2.

Installation
------------
To install the plugin:
 - Upload the tar.gz file in OJS/OMP/OPS (Management > Website Settings > Plugins > Generic Plugins)

Support
---------------
A documentation on this plugin can be found in pluginDocumentation_customLocale.md
