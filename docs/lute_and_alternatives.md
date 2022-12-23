## Should I use Lute, LWT, Hugo's fork, or something else?

Sensible question, so here's my take:

If you _just want to use something_, I'd recommend [Lingq](https://www.lingq.com/en/)!  It's a paid solution, they deal with the software and the headaches, you just pay for it and use it.

If you want to run your own thing on your machine, the options I can see are:

* The [original LWT project](https://sourceforge.net/projects/learning-with-texts/) is, as far as I can tell, still *massively popular.*  Even if the code is old, and it might have bugs or be slow, it has the advantage of being tried-and-tested, and you might find people who can help you with issues.
* [Hugo Fara's fork](https://github.com/hugofara) has taken the original project and refined the code, but has kept the same features and overall design/architecture.
* [FLTR](https://sourceforge.net/projects/foreign-language-text-reader/), a Java project by the same author of LWT.

### So why even consider Lute?

Top answer: For me, it feels faster and lighter than LWT, and it has just enough features to be useful.  The UI's a bit leaner, if that matters to you.  I am currently using Lute to read texts in Spanish, and it works great.  It should work very well for other left-to-right languages like French, German, English, Italian, etc.

**Note:** if you are currently using LWT, you should be able to export a copy of your database, and start using it with Lute, after setting up the necessary software.  You'll just need to migrate your old LWT-style database to the new Lute database (see [database migrations](./db/README.md)).

More geeky reasons:

I believe that Lute is a useful contribution to the language-learning-software landscape.  It implements the core features of LWT in a fraction of the size of the LWT codebase, using modern PHP tools, and with automated tests for stability.

This might not mean much to regular peeps who just want to learn languages. :-)  At least, at the moment ...

But if you are into software, like the idea of LWT, and want to contribute to an open-source project, I believe that Lute is a compelling place to start.

### Why might you **not** use Lute?

* Currently, Lute is an MVP, and many LWT features were [removed for the MVP](lwt_features_that_were_removed.md).  If that's a dealbreaker, Lute's not for you.
* Lute currently doesn't support languages like Japanese.  I didn't have good test data for that, and so couldn't implement it, or port the old LWT code over for it.
* Like LWT, Lute is currently the work of just one guy ... me.  And it has a user base of one ... also me.  I'll answer questions and fix issues as I can, but it's _currently_ just me working on this project.
