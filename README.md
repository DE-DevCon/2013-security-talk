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

### #3: Trusting Client-Side Data
Often, when we are writing web applications, we want to treat our entire
application as secure.  If our code sets a variable initially, we feel a sense
of control over it.  But as soon as the data is coming from a potentially
untrustworthy source (like the user), we need to treat it as a malicious piece
of data.

Cookies are a great example of this.  Even when we don't have any javascript
(like in this example application) and we set the cookies in our php code, we
cannot trust them.  They are implemented as an HTTP header and are sent by the
user's client (typically their browser) in every request to our site.  Nothing
is stopping a malicious user from editing the values to bypass our security.

For example, in our example app, we authenticate a user by checking that they
have a `handle` cookie set.  If they do, then we consider them as fully
authenticated as that user.  But if `boris` sets his `handle` cookie to
`rocky`, then our app will be none the wiser!

We can fix this vulnerability by sharing a secret with the user when they
authenticate using their actual credentials.  We need an unguessable value that
uniquely identifies the user.  A hash of a variety of fields is an easy way to
get this (random data can be even better) like in our example.  As long as
`boris` cannot guess what `rocky`'s hash is even if they know the hash
algorithm used, then we are secure.

The best way to validate this hash is to actually store it per-user.  This
opens up a lot of possibilities as far as security goes, including:

* The hash algorithm doesn't have to be repeatable on future requests.  This
  allows for random data that allows the hash to be more secure.
* The ability to kill someone's session by changing their hash.  This helps
  provide an easy way to force someone to reauthenticate if you think they
  hacked someone else's account.
* By storing multiple hashes, possibly with other data attached (IP, Location
  info, Browser info, etc), you can provide your users with a list of their
  active sessions and allow them to invalidate other sessions that may not be
  legit (this step should require re-authentication with user credentials).
  This can also help save your users when they left themselves logged in on a
  public computer.

So what data is not trustable?  Anything that the client provides is untrusted:
form fields, query parameters, urls, request headers (including cookies),
request body and websocket data.  The security measures to ensure that the data
is valid differ from field to field but many can be treated similarly to the
`handle` cookie discussed above, by validating fields as strictly as possible,
and by properly encoding the fields before using them.

Not only is that client data untrusted, but you also need to potentially be
wary of data that came from a client somewhere else.  As your application
grows, so does the number of potential vulnerabilities.  If one location in
your application is weak and allows the client to save unsafe data to your
database, then other parts of your application could be affected even if they
don't have any external vulnerabilities.  Therefore, it is sometimes desirable
from a security standpoint to do validation and sanitization even on data from
your database - who knows how it got there in the first place!
