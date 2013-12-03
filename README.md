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

### #2: Injection Vulnerabilities

Even after removing the sensitive information, an attacker can still easily see
that single quotes are not being properly handled, and can use this to their
advantage.  By not properly encoding the form fields before using them in a
database query, we allow an attacker to rewrite our query to do nasty things.

This particular vulnerability is known as SQL Injection, but SQL is not the
only location an attacker can inject their own behavior. For example:

* `echo file_get_contents("/useruploads/{$_POST['username']}");` with the
  username set to `../etc/passwd`
* `exec("file {$userFile}")` with the user file set to `doesntmatter; rm -fr /`
* `echo "<div>Welcome, {$username}</div>"`, with the username set to
  `<script src="http://evilsite/script.js"></script>` (also an example of XSS)
* and many, many more

In this example application, trying to use the username `rocky' --` allows us
to login as the law-abiding user `rocky`, rather than our evil user `boris`.

The general process to fixing injection vulnerabilities is to properly encode
untrusted data before using it.  There is no foolproof encoding that works for
all places, but many libraries include methods to properly encode user data.
Sometimes libraries will provide simple functions for escaping single fields or
longer sections (eg., `pg_escape_literal`, ` SolrUtils::escapeQueryChars`,
`escapeshellarg`).  When available, however, it is usually preferable to use
methods that bundle in the escaping for you (eg. `pg_query_params` and other
bind param solutions).

Fixing this in our example application means using `pg_query_params` rather
than `pg_query`.

Note that in our application, we had vulnerabilities everwhere that we queried
the database, and boris could have updated his tweet with the description
`an evil tweet.', user_handle='rocky`, and it would have made the evil tweet
appear as though rocky posted it rather than him.

It is not just login or checkout forms that need to be looked at.  Anywhere
that user data is handled, it should be properly encoded.  Any of the above
examples could have been used to do much nastier things, like
`'; DROP TABLE tweets; DROP TABLE users; --`.
