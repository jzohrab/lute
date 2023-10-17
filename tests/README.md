# Tests

## V3-port marks tests for Lute v3

The tests in this repo are used as a rough guide for what needs to be implemented in Lute v3, the Python rewrite of Lute (ref https://github.com/jzohrab/lute/wiki/Project-goal).

The tests in this project ("public function test_...") are annotated with a comment: `// V3-port: TODO`

To see all of the TODOs:

```
composer dev:find V3-port | grep TODO
```

As they're completed, the TODO should be changed to DONE, or IGNORED, or whatever, with other comments as needed, such as where they're implemented.

Lute v3 is currently at https://github.com/jzohrab/lute_v3.
