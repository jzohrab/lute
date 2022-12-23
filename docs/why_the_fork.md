# Why the fork?

The original Learning With Texts code is ... tough.  Per the author at https://learning-with-texts.sourceforge.io/:

> My programming style is quite chaotic, and my software is mostly undocumented.

Regardless, the original LWT author released a _super_ idea and a working project!

Hugo Fara then picked up the project and started working at it in earnest in [his fork](https://github.com/HugoFara/lwt), making massive improvements to the code structure.

**Thanks to both of these guys!**

## Initial contributions to LWT

I started using LWT in Oct/Nov 2022.  As a (former-ish) dev, there were some things in the code that _really bothered_ me, and potentially blocked me from adding features I wanted for myself.  I proposed some patches, but Hugo wanted to put things on hold as he dreamed up another version of LWT.

Since Hugo's version of LWT was working for me, I forked and started to make my own changes.  I soon got tired of struggling with the existing code, and started implementing small subfeatures with something like the ["strangler pattern"](https://microservices.io/patterns/refactoring/strangler-application.html).  I introduced [the Symfony framework](./symfony.md) for small sections, and then figured I could get an MVP out the door that did away with all of the legacy code.
