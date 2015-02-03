concrete5 Integrated Localization
=================================

Work-in-progress project to integrate a translation system within concrete5.org website.

Available components
--------------------


### Jobs

`Fetch git translations`
Fetches the two GitHub repositories and extracts translatable strings for all the tagged versions as well as for the development branches.
**Warning** the first execution of this job may require a few minutes. Subsequent executions will be much faster (it should complete in seconds).


### Block Types

`Integrated Package Languages Builder`
For package developers: they can upload their package and download the language files for that package


### User Groups

`Translations administrators`
 Users belonging to this group will have global locale administration rights

`Locale administrators for xx_XX` (where `xx_XX` is a locale identifier)
Users belonging to this group will have locale-specific administration rights

`Locale translators for xx_XX` (where `xx_XX` is a locale identifier)
Users belonging to this group will have locale-specific translation rights

`Aspirant locale translators for xx_XX` (where `xx_XX` is a locale identifier)
Users that want start translating a specific locale will be inserted in this group


### Single Pages

`/integrated_localization/locales`
to manage locales (only for site administrators and for global locale administrators)

`/integrated_localization/groups`
to manage locales (only for site administrators and for global locale administrators)
