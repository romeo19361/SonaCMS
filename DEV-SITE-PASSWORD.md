# Password-Protecting the Development Site

This guide puts your **entire development site** behind a single password, so it
can't be viewed by anyone without it. It works by adding a few lines to the
existing `.htaccess` file at the site root.

This is completely separate from logging into SonaCMS. Visitors will see a
browser password box **first**; the normal SonaCMS login still applies after
that.

> **Applies to Apache hosting.** If your development host runs nginx instead,
> `.htaccess` files are ignored — let us know and we'll supply the nginx
> equivalent.

---

## Before you start

You'll be editing the `.htaccess` file in the site's root folder (the same
folder that contains `index.php` and the `assets` folder). This file already
contains important settings, so the golden rule is **add to it, don't replace
it**.

---

## Step 1 — Back up the current `.htaccess`

Using SFTP or your host's file manager, make a copy of the root `.htaccess`
and name the copy `.htaccess.bak`.

If anything goes wrong later, deleting your edited version and renaming
`.htaccess.bak` back to `.htaccess` restores the site instantly.

---

## Step 2 — Add the password block

Open the root `.htaccess` and paste these four lines at the very **top** of the
file, above everything already there:

```apache
AuthType Basic
AuthName "LiveAid — Development Site"
AuthUserFile /home/USERNAME/.htpasswd
Require valid-user
```

Leave all the existing lines below it exactly as they are.

*(`/home/USERNAME/` is a placeholder — you'll set the correct path in Step 4.)*

---

## Step 3 — Create the password file

The `.htpasswd` file holds the login and password.

**If you have SSH access**, run:

```bash
htpasswd -c /home/USERNAME/.htpasswd liveaid
```

It will ask you to type the password twice, then create the file.

**If you don't have SSH**, use any "htpasswd generator" website (choose the
**bcrypt** option). Enter a username of `liveaid` and your chosen password.
It will produce a single line like:

```
liveaid:$2y$10$......
```

Create a plain text file named `.htpasswd`, paste that one line into it, and
upload it to the location you'll set in Step 4.

> The username `liveaid` is just a label — the password is what matters. One
> shared login covers the whole development site.

---

## Step 4 — Set the correct file path

`/home/USERNAME/` in both steps above is a placeholder. It must be the **full
path on the server** to where your `.htpasswd` file lives, and it must be
**identical** in the `.htaccess` and in your actual upload location.

For security, `.htpasswd` should sit **above** the public web folder (i.e. not
inside `public_html`), so it can never be downloaded through a browser.

**To find the correct path without SSH:**

1. Create a file named `whereami.php` containing this single line:
   ```php
   <?php echo __DIR__;
   ```
2. Upload it to the site root and open it in your browser
   (e.g. `https://your-dev-site.com/whereami.php`).
3. It will print the site's full path, for example:
   `/home/liveaid/public_html`
4. The home folder is the part **above** `public_html` — here that would be
   `/home/liveaid/`. Use that for `.htpasswd`.
5. **Delete `whereami.php`** once you have the path.

Update the `AuthUserFile` line in `.htaccess` to match, e.g.:

```apache
AuthUserFile /home/liveaid/.htpasswd
```

---

## Step 5 — Test it

Open the development site in a fresh browser tab (or a private/incognito
window). You should see a password box before any part of the site loads.
Enter `liveaid` and your password to get in.

---

## If something goes wrong

- **You get a "500 Internal Server Error":** the `AuthUserFile` path is almost
  certainly wrong or the `.htpasswd` file isn't where it says. Re-check Step 4,
  or restore `.htaccess.bak` from Step 1 and try again.
- **No password box appears:** the host may not allow `.htaccess` overrides, or
  it may be nginx rather than Apache. Contact us and we'll sort the alternative.

---

## Good to know

- This gate sits **in front of** SonaCMS. You'll enter the site password first,
  then log into SonaCMS as normal — two separate passwords, by design.
- To **change the password** later, just re-run Step 3 (SSH: drop the `-c` flag
  so it edits the existing file rather than recreating it).
- To **remove protection** entirely (e.g. at launch), delete the four lines you
  added in Step 2.

---

*Prepared by Tecky · August 2026*