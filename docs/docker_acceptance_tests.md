To run acceptance tests using a running Docker container:

* Build and start the container
* in .env, change the following:

```
# Change env to prod -- docker only has prod dependencies!
APP_ENV=prod

# Add this:
EXTERNAL_URI=http://localhost:8000
```

The AcceptanceTestBase.php file checks the ENV for EXTERNAL_URI, and if found, it starts the Panther client with that URL.