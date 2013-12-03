# 12bcon Web Security Presentation

A PHP Application used to demonstrate some basic security.

## Security Vulnerabilities
There are many vulnerabilities in this code.  We will work through several of
the more egregious cases, recognize them and find a way to fix them.

### #1: Debug in Production
While so-called "security through obscurity" is nothing that you should rely
on, it is generally better to hide sensitive information about how your
application works from potential ne'er-do-well's.

For example, try logging in to the site using a username that contains an
apostrophe (single quote) like `o'malley`.  You should see an error like this:
```
pg_query(): Query failed: ERROR: syntax error at or near "malley" LINE 1: SELECT * FROM users WHERE handle='o'malley' AND password='pa... ^
```
along with a lot of other identifying information (file names, line numbers,
etc.) that help an attacker determine that this is a Slim Framework PHP
application, using Postgres as a database, and not properly escaping user
input.

Fixing this is application dependent, so search around for debug code in your
code and included libraries.  PHP's `error_reporting` setting can be used to
keep errors from appearing to users.  Frameworks and individual applications
also can have their own error reporting/debug mechanisms.  For example, to fix
the issue in this codebase, ensuring that Slim's `debug` setting was set to
`false` removed the sensitive information and replaced it with a much less
revealing error page.
