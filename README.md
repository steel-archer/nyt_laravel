**Test task**

**Note:**
* Laravel Sail was used to create the app skeleton.
* Although the NYT API documentation states that the `isbn` parameter can accept multiple ISBNs, it doesn't work for me (it simply returns no records). Therefore, I made this parameter a regular integer.

**Additional Implemented Features:**
* `consider API versioning`: Added a version parameter that can be 1, 2, or 3. The version value is also included in the output. The response structure varies by version (although versions 2 and 3 appear to be identical).
* `caching`: File-based caching is used. NYT API call results are cached to speed up repeated requests. However, caching is disabled in tests.

**How to Set Up the Environment:**
* `git clone git@github.com:steel-archer/nyt_laravel.git laravel_nyt`
* `cd nyt_laravel2`
* Set your API key in the file `.env` (`NYT_API_KEY` variable).
* `docker compose up -d`
* `docker compose exec laravel.test composer install`

**Environment Notes (Possible Issue and Solution):**
Occasionally, localhost may not function correctly. Here’s what works for me:
* `sudo lsof -i :8989`
* There can be multiple listeners of port `8989` used by app. Example output:
* `COMMAND      PID USER   FD   TYPE  DEVICE SIZE/OFF NODE NAME`
* `docker-pr 297440 root    7u  IPv4 3818230      0t0  TCP *:8989 (LISTEN)`
* `docker-pr 297447 root    7u  IPv6 3818231      0t0  TCP *:8989 (LISTEN)`
* Run the following commands to resolve the issue (replace `pids` with your actual process IDs):
* `sudo kill -9 297440 297447`
* `docker compose down --remove-orphans`
* `docker compose up -d`

**How to Use the App:**
* Open http://localhost:8989/api/1/best-seller-history in your browser (you can also add parameters to the URL).

**How to Run Tests:**
`docker compose exec laravel.test php artisan test --coverage`

**Potential Improvements:**
* CI/CD Pipelines via GitHub Actions – We can run tests on push and fail the build if any tests fail or if code coverage is insufficient.
* Request Timeout Handling – To enforce time constraints, we could integrate a library like ReactPHP to ensure requests fail if they exceed a predefined time limit.