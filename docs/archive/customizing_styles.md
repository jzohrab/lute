# Customizing styles

Lute doesn't have "theming" support yet, but you _can_ modify the styles with CSS.

In `public/css/styles-overrides.css`, you can define your own styles to replace the existing styles in `public/css/styles.css`.  Don't edit `styles.css`, just redefine your own.

For example, if you wanted the words to be very large in the reading pane, you could write the following:

```
span.textitem {
    font-size: 24px;
}
```

If the changes don't show up on page refresh, you may need to clear the application cache from the command line:

```
composer dev:nukecache
```