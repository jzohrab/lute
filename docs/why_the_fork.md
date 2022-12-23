# Why the fork?

TL;DR: I felt that LWT was **an extremely important idea**, but I felt that **its implementation made it hard to fix problems, and created barriers for its improvement**.

<hr />

I started using [Hugo Fara's fork of LWT](https://github.com/HugoFara/lwt) in Oct/Nov 2022.  I submitted a few patches, but Hugo wanted to put things on hold as he dreamed up another version of LWT.

* Initially, I just wanted a new feature: adding "parent terms" to terms.  To me, it doesn't make sense to think of a conjugated form of a verb ("I _speak_", "yo _hablo_") as a separate thing from the root form ("to speak", "_hablar_").  I added the feature to LWT, but it was very tough.
* There were some bugs in LWT that were impossible to track down.  For example, when adding multi-term expressions, LWT would sometimes find them, and sometimes miss them.  Lute corrects those issues, and adds a series of automated tests to help track down those problems.
* As a former dev, there were some things about LWT that I simply couldn't get behind: lack of automated testing, tough database management, tough architecture, etc.  Per the author at https://learning-with-texts.sourceforge.io/: "My programming style is quite chaotic, and my software is mostly undocumented."  That's what happens when you create a brand new system -- Lute takes advantage of the things learned in that code and in Hugo's fork to create a reasonably solid starting point.

Even if Lute doesn't become "the new LWT" that I hope it can be, perhaps it will be useful as a reference implementation.
