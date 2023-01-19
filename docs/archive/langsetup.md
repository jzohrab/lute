# Language Setup

Lute's language setup is practically the same as LWT's.

When creating a new Language, you can use one of the existing languages as a template by selecting the dropdown at the top of the form and clicking "go."  Sensible defaults are set.

## Dictionary 1 and 2

"Dictionary 1" and "Dictionary 2" are used for term lookup.

When you have the "Term" form open in the right-hand pane while reading, if you click the small arrow icon next to the term, either Dictionary 1 or 2 will be shown in the lower frame (or in the right frame, if you're editing the term from the Term listing page).  Click the arrow repeatedly to cycle through the dictionary.

The dictionary link entry on the form must contain "###".  Lute substitutes that with the actual term you're looking up.

## Image lookups

To use a Bing image lookup, you can put the following into either Dictionary 1 or Dictionary 2:

```
https://www.bing.com/images/search?q=###&form=HDRSC2&first=1&tsc=ImageHoverTitle
```

_(I admit I'm not 100% sure on how to specify French only images, or German-only images, but just using a standard string above appears to work for the languages I've tried!)_

Note that the Term form (the right-hand frame when reading) has an "eye" icon next to the term ... you can click on that and it does a common bing image search (disregarding your custom image search url you might have saved with the language).

## Sentence lookups

You could use either DeepL or Google translate for sentence lookups.

## External dictionary lookups

Some sites, like DeepL and Google Translate, don't work when embedded within sites.  These sites have to be viewed in separate pop-up windows, outside of Lute itself.

To mark a URL as "external", precede it by an asterisk, e.g.:

```
*https://www.deepl.com/translator#es/en/###
```