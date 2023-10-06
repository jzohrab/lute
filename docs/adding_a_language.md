# Adding a language

The languages and stories included in the demo database (for new
installs) are all loaded from data files in `./demo`.

Copy the `demo/languages/_language.yaml.example` to `{newlang}.yaml`, and edit it.

To add a story for the language, also add a text file in the `./demo/stories` folder.

Then run `composer dev:data:load` to see the new lang and story.

To update the demo db that new installs see, run `composer db:create:demo`