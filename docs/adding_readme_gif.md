# Adding the gif to the readme

This was more trouble than it should have been, but that's ok.

I tried putting a gif in my Dropbox/Public folder, but GitHub wouldn't serve unless the gif was quite small.  When the gif was large, or even about ~5mb, the image wouldn't show up.  Per https://stackoverflow.com/questions/37750270/unable-to-embed-a-gif-on-imgur-to-a-readme-md-on-github:

> GitHub routes all content through https://camo.githubusercontent.com/ even for external source now. Sharing a file that is bigger than the size limit (10MB) will get you a "Content length exceeded" error.

I first recorded a gif using Quicktime, edited it with Shotcut, exported a .mov, and then cut it down a fair bit using gifsicle, following the instructions here:

https://gist.github.com/dergachev/4627207

The command I used:

```
ffmpeg -i in.mov -s 600x400 -pix_fmt rgb24 -r 10 -f gif - | gifsicle --optimize=3 --delay=1 > out.gif
```

I then resized that to 80% of its size using  https://ezgif.com/resize

That file was ~2.5 mb.

If I tried to use that gif in the readme, I got nothing:

```
![small lute db](https://www.dropbox.com/s/qq2e7fb2sg9lrvy/small_lute.gif?dl=0)
```

This rocking post, https://www.seancdavis.com/posts/three-ways-to-add-image-to-github-readme/, had tip #3, which I used to have github host that small file for me.  I dragged the small gif file to a new issue, and copied the link that GitHub generated:

```
![small_lute](https://user-images.githubusercontent.com/1637133/209414488-1d2d63db-6790-452c-bfae-837afed822b2.gif)
```

Copy-paste, and bob's your uncle.

What a waste of time this was.