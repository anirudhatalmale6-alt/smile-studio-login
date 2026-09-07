# Smile Studio Login

A branded WordPress login screen, and — optionally — a login that does not live at
`wp-login.php`.

Two things, deliberately separate:

**1. How it looks.** `wp-login.php` is restyled. The form, the fields and the
authentication are WordPress's own; nothing is intercepted and no credential is
handled by this plugin. A login page that "handles" passwords itself is a
liability.

**2. Where it lives.** Set a slug and the login moves to `/your-slug/`, while
`wp-login.php` answers with the site's ordinary 404 — not a block page, because a
block page still says "there is a login here". `/wp-admin/` for a logged-out
visitor goes to the homepage rather than redirecting to the login, since that
redirect is itself a signpost.

Be clear about what the second part is: **obscurity, not security.** It does not
make a weak password strong. What it removes is the constant automated traffic,
because bulk scanners ask for `/wp-login.php` by name and give up when it is not
there.

## Not getting locked out

Add to `wp-config.php`:

```php
define( 'SSL_EMERGENCY_KEY', 'something-long-and-private' );
```

Then `https://example.com/wp-login.php?something-long-and-private=1` shows the
normal login again. The key grants nothing on its own — it only makes the page
visible. You still need the password.

## Settings

Settings → Studio Login. Brand name, mark or logo, every line of copy, the four
colours, and the login address. Nothing needs a developer, which is the point:
anything that needs one is a thing that never gets changed.

## Notes for whoever maintains this next

- `ssl_route()` hooks **`wp_loaded`, not `plugins_loaded`.** Loading
  `wp-login.php` at `plugins_loaded` gives a 500 — `$wp_query` does not exist
  yet, and the first plugin whose `wp_head` callback calls
  `get_queried_object()` fatals on null.
- Requests that arrive by the new slug define `SSL_VIA_SLUG`, because the 404
  guard hooks `login_init` and would otherwise 404 the new address too.
- Checkbox rules are written `body.login input[type="checkbox"]`. Core's are
  `.wp-core-ui input[type=checkbox]`, which ties on specificity, and a tie is
  settled by load order.
- `.ssl-divider` and `.ssl-footnote` carry `width:100%`. They are flex items of
  the body, and a flex item with `margin:auto` and no width shrink-wraps to its
  content.
- If a styling change refuses to appear, check whether LiteSpeed Cache is
  caching the login page (`Cache → Cache Login Page`). It does by default.

## Licence

GPLv2 or later.
