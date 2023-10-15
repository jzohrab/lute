Docker on a Mac has some speed challenges.

Every situation is different, I can't document everything, but here's what I use on my own Mac M2 pro.

The below gives _very very_ good performance, per https://www.tyler-wright.com/using-colima-on-an-m1-m2-mac/


```
colima start --arch aarch64 --vm-type=vz --vz-rosetta
```

I had to delete my old config, per https://github.com/abiosoft/colima/issues/750:

```
colima delete default
```

Then `docker compose up` worked fine, speed very good.