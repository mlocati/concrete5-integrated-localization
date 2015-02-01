concrete5 Integrated Localization
=================================

Work-in-progress project to integrate a translation system within concrete5.org website.

Available components
--------------------

### Jobs

- `Fetch git translations`
  Fetches the two GitHub repositories and extracts translatable strings for all the tagged versions as well as for the development branches.
  **Warning** the first execution of this job may require a few minutes. Subsequent executions will be much faster (it should complete in seconds).

### Helpers

- `TranslationsSource`
  Offers these useful methods
  - `processPackageZip` to extract strings from a .zip file containing a package
  - `processPackageDirectory` to extract strings from a directory containing a package
  - `getAvailableLocales` to get the locales currently defined (offers also plural rules info)
  - `importTranslations` to import translated strings
  - `loadPackageTranslations` to retrieve the translatable/translated strings