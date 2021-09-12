Key data
========

- name of the plugin: Custom Locale Plugin
- author: Carola Fanselow (updates by Alec Smecher, Erik Hanson)
- current version: 1.1.0.0
- see .travis.yml for compatibility information
- github link: https://github.com/asmecher/customLocale.git
- community plugin: yes
- date: 2020/05/05

Description
===========

This plugin allows to customize locales via the OJS/OMP/OPS GUI. The default locales are replaced but not overwritten and can easily be restored. A simple documentation can be printed that lists all changes made to the locales. The locales can be customized in the tab 'locale' in management > settings > website. There is a search function for files and for locale keys within the files. The documentation can be printed in the plugin's settings section.

 
Implementation
==============

Hooks
-----
- used hooks: 4

		PKPLocale::registerLocaleFile
		LoadComponentHandler
		Template::Settings::website
		LoadHandler

New pages
---------
- new pages: 1

		management/settings/website, tab 'Locales'

Templates
---------
- templates that substitute other templates: 0
- templates that are modified with template hooks: 0
- new/additional templates: 2
	
		customLocaleGridFilter.tpl
		localeFile.tpl

Database access, server access
------------------------------
- reading access to OJS/OMP/OPS tables: 0
- writing access to OJS/OMP/OPS tables: 0
- new tables: 0
- nonrecurring server access: no
- recurring server access: yes

		creating, reading from and writing to files in [files_dir]/[presses|journals]/%contextId%/customLocale
 
Classes, plugins, external software
-----------------------------------
- OJS/OMP/OPS classes used (php): 19
	
		GenericPlugin
		FileManager
		Handler
		GridHandler
		RecursiveDirectoryIterator
		RecursiveIteratorIterator
		RegexIterator
		DataObject
		GridCellProvider
		AjaxModal
		EditableLocaleFile
		LocaleFileForm
		JSONMessage
		GridColumn
		PagingFeature
		LinkAction
		RedirectAction
		Form
		TemplateManager
		NotificationManager
	
- OJS/OMP/OPS classes used (js, jquery, ajax): 0

		ClientFormHandler
		AjaxFormHandler

- necessary plugins: 0
- optional plugins: 0
- use of external software: no
- file upload: no
 
Metrics
-------
- number of files: 21
- lines of code: 1k LOC

Settings
--------
- settings: no

Plugin category
---------------
- plugin category: generic

Other
=====
- does using the plugin require special (background)-knowledge?: no
- access restrictions: restricted to admins and press managers
- adds css: yes
