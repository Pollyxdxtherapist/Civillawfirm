# civillawfirm.in — website source

This is the complete website for **Civil Law Firm**, Ground Floor, 121/B Sitaram Ghosh Street,
Kolkata 700009.

It is built from **plain HTML and CSS files**. There is no WordPress, no
database, no login, no "build step" and no programming language to learn. Every
page is a file you can open in Notepad, TextEdit or VS Code, change a few words,
save, and put back online.

The site is in **three languages**: English (the main site), Hindi (in the `hi`
folder) and Bengali (in the `bn` folder). Each language is a real set of pages
with its own web address, which is what makes them findable on Google.

---

## Contents

1. [Before the site goes live: five things to fill in](#1-before-the-site-goes-live-five-things-to-fill-in)
2. [How to look at the site on your own computer](#2-how-to-look-at-the-site-on-your-own-computer)
3. [Putting it online (hosting)](#3-putting-it-online-hosting)
4. [Getting found on Google (Search Console)](#4-getting-found-on-google-search-console)
5. [Everyday changes](#5-everyday-changes)
   - [Change a phone number](#change-a-phone-number)
   - [Change the email address](#change-the-email-address)
   - [Change the chambers hours or address](#change-the-chambers-hours-or-address)
   - [Replace a photo placeholder with a real photograph](#replace-a-photo-placeholder-with-a-real-photograph)
   - [Add a blog post (an "Insight")](#add-a-blog-post-an-insight)
   - [Add a new practice-area page](#add-a-new-practice-area-page)
   - [Change the colours](#change-the-colours)
6. [Rules that must not be broken (Bar Council of India)](#6-rules-that-must-not-be-broken-bar-council-of-india)
7. [Things that keep the site findable — please do not remove them](#7-things-that-keep-the-site-findable--please-do-not-remove-them)
8. [What every file and folder is for](#8-what-every-file-and-folder-is-for)
9. [Have the legal pages reviewed](#9-have-the-legal-pages-reviewed)

---

## 1. Before the site goes live: five things to fill in

Everything on the site is real and final **except** the five items below. Each
one is marked in the files with the word `PLACEHOLDER` in capital letters, so
you can always find what is left to do by searching the folder for
`PLACEHOLDER`.

### (a) The firm's email address

Already filled in: `contact@civillawfirm.in`, in the footer of every page, on
the Contact page, and inside the "structured data" block at the top of each
page. If the address ever changes, search all files for the old address and
replace it with the new one.

On a Mac or Linux computer you can do that in one go. Open Terminal in this
folder and run (replacing both addresses as needed):

```
grep -rl "contact@civillawfirm.in" . --include=*.html --include=*.txt \
  | xargs sed -i 's/contact@civillawfirm\.in/office@civillawfirm.in/g'
```

On Windows, use VS Code: press `Ctrl+Shift+H` (Replace in Files), type the old
address in the top box and the new one in the second box, then click
"Replace All".

### (b) The WhatsApp number

The WhatsApp button everywhere points to a dedicated WhatsApp number,
**+91 91233 05701**, which is separate from either advocate's call number.

If this number ever changes, replace `919123305701` everywhere:

```
grep -rl "919123305701" . --include=*.html --include=*.txt \
  | xargs sed -i 's/919123305701/9XXXXXXXXX/g'
```

That single command also covers the two `data-wa-number="..."` values on the
Contact pages (`/contact/index.html`, `/hi/contact/index.html`,
`/bn/contact/index.html`) and the fallback default in `js/main.js`.

### (c) The contact form key (Web3Forms)

The enquiry form on the Contact page needs a free key so that messages arrive
by email. It takes two minutes and no card:

1. Go to <https://web3forms.com>, type the firm's email address, and it will
   email you an **access key** (a long code).
2. Search all files for `[PLACEHOLDER-WEB3FORMS-ACCESS-KEY]` and replace it with
   that key. It appears once on each of the three Contact pages.

Until this is done, the form's "Send message" button will not deliver anything —
but the **"Send on WhatsApp"** button beside it works immediately, and so does
the Call button on the Contact page.

(If you prefer Formspree instead, change the form's `action="..."` address to
your Formspree endpoint and delete the hidden `access_key` line.)

### (d) The Google Search Console token

See [section 4](#4-getting-found-on-google-search-console). Replace
`[PLACEHOLDER-GSC-VERIFICATION-TOKEN]` with the token Google gives you.

### (e) Photographs

There are no photographs yet. Every place a photograph should go shows a grey
placeholder box with a caption saying what belongs there:

- each advocate (on their profile page)
- the chambers interior (bookshelf / desk)
- the street exterior (the building entrance)
- the locality (on the home page)

See [Replace a photo placeholder](#replace-a-photo-placeholder-with-a-real-photograph).

The sharing picture (what appears when someone shares a link on WhatsApp or
Facebook) is `assets/og-image.png`. It is a plain beige placeholder card.
`assets/og-image.svg` is the same card **with the firm's name and address on
it** — open it in any browser, and if you want a nicer sharing picture, have
someone export that file as a 1200 × 630 pixel PNG named `og-image.png` and
replace the existing one.

---

## 2. How to look at the site on your own computer

The addresses inside the site start with `/`, the way they will on the real
website. Because of that, **double-clicking `index.html` will show the page but
the menu links will not work.** Use a tiny local web server instead — it is one
command and needs nothing installed on a Mac:

1. Open Terminal (Mac) or Command Prompt (Windows) in this folder.
2. Run: `python3 -m http.server 8000`
   (on Windows it may be `python -m http.server 8000`)
3. Open <http://localhost:8000> in your browser. The whole site works, exactly
   as it will online.
4. Press `Ctrl+C` in the Terminal window when you are finished.

Alternatively, install the free **Live Server** extension in VS Code and click
"Go Live".

---

## 3. Putting it online (hosting)

Any of these three will host this site **free**. All you do is give them this
folder; there is nothing to configure.

### Cloudflare Pages (recommended)

1. Put this folder in a GitHub repository (it is already one).
2. Sign in at <https://dash.cloudflare.com> → **Workers & Pages** → **Create** →
   **Pages** → **Connect to Git**, and choose this repository.
3. When it asks for build settings:
   - Framework preset: **None**
   - Build command: **leave empty**
   - Build output directory: `/`
4. Click **Save and Deploy**.
5. Then **Custom domains** → add `civillawfirm.in` and `www.civillawfirm.in`,
   and follow the instructions to point the domain at Cloudflare.

After that, every time you change a file and push it to GitHub, the website
updates by itself within a minute.

### Netlify

Drag this folder onto <https://app.netlify.com/drop>, or connect the GitHub
repository. Leave the build command empty and set the publish directory to `.`.
Then add the custom domain under **Domain settings**.

### GitHub Pages

In the repository, go to **Settings → Pages**, and under "Build and deployment"
choose **Deploy from a branch**, branch `main`, folder `/ (root)`. Then add
`civillawfirm.in` under **Custom domain**.

Two notes for GitHub Pages: the file `.nojekyll` in this folder is required (it
is already here) — it stops GitHub from hiding the `insights/_template` folder.
And GitHub Pages must be used with the custom domain `civillawfirm.in`, not the
`username.github.io/repository` style address, because the links inside the site
start from the root of the domain.

---

## 4. Getting found on Google (Search Console)

This is the step that tells you whether the site is working. Do it once, on the
day the site goes live.

1. Go to <https://search.google.com/search-console> and sign in with the firm's
   Google account.
2. Click **Add property** → **URL prefix** → type `https://civillawfirm.in`.
3. Choose the **HTML tag** verification method. Google shows a line like
   `<meta name="google-site-verification" content="AbCd1234..." />`. Copy only
   the long code inside `content="..."`.
4. Search all files in this folder for `[PLACEHOLDER-GSC-VERIFICATION-TOKEN]`
   and replace it with that code (same method as the email in section 1a). It is
   in the top part of every page.
5. Put the updated files online, then click **Verify** in Search Console.
6. Still in Search Console, open **Sitemaps** in the left menu, type
   `sitemap.xml`, and click **Submit**. This tells Google about all 63 pages
   (21 pages × 3 languages) in one go.

After a week or two, **Search Console → Performance** will show you the actual
words people typed into Google before arriving at the site. That list is the
most useful thing you will get from the whole exercise: it tells you which
practice-area page to expand, and what to write the next Insight about.

Useful extras:

- Also add the site to **Bing Webmaster Tools** (<https://www.bing.com/webmasters>);
  it takes two minutes and can import everything from Search Console. Bing feeds
  several AI assistants.
- Create a free **Google Business Profile** for the chambers
  (<https://business.google.com>) with the same name, address, phone number and
  hours as this site. For a local firm this is often worth as much as the
  website itself. Keep the details identical to the ones here.
- The file `llms.txt` is a plain summary of the firm for AI assistants such as
  ChatGPT, Claude and Perplexity. `robots.txt` explicitly welcomes their
  crawlers. Both are already set up; if the firm's details change, update
  `llms.txt` too.

### Analytics (optional, later)

Every page has a commented-out slot near the top for a lightweight visitor
counter. If the firm ever wants one, uncomment the Plausible line (or paste a
GA4 snippet in the same place) on every page. Nothing is tracking anybody at
present, and the Privacy Policy says so — if you switch a counter on, add a line
about it to the Privacy Policy.

---

## 5. Everyday changes

A general rule: **each page is one file.** If a page's address is
`civillawfirm.in/practice-areas/recovery-suits/`, its file is
`practice-areas/recovery-suits/index.html`. The Hindi version of the same page
is `hi/practice-areas/recovery-suits/index.html`, and the Bengali one is in
`bn/`.

Text you can safely edit is the ordinary English (or Hindi or Bengali) between
the angle-bracket tags. For example, in

```html
<p>The chambers is open every day.</p>
```

change only `The chambers is open every day.` and leave `<p>` and `</p>` alone.

### Change a phone number

A phone number appears in three forms, and all three must be changed:

| Form | Looks like | Where |
| --- | --- | --- |
| The dialling link | `href="tel:+917604029237"` | the Call button on the Contact page, the footer of every page, and Adv Aditya Kumar Jain's fact list |
| The printed number | `+91 76040 29237` | wherever the number is shown as words |

The WhatsApp number is separate from this call number — see
[section 1b](#b-the-whatsapp-number) if you need to change that instead.

**Where the Call button is.** A click-to-call *button* appears on the **Contact
page only**. Every other page offers WhatsApp plus a link through to Contact.
The phone number itself is still printed, as a plain click-to-call line, in
the footer of every page and in Adv Aditya Kumar Jain's fact list — that is
deliberate, because the name, address and telephone number appearing
consistently on every page is one of the things Google uses to rank a local
firm.

Because the number is printed in plain text on every page (which is exactly what
lets Google and AI assistants read it), it appears in many files. Do not edit
them one by one — replace all of them at once.

**Mac / Linux**, in Terminal in this folder — for example, changing Aditya's
number from `76040 29237` to a new number `98765 43210`:

```
grep -rl "7604029237" . --include=*.html --include=*.txt --include=*.js \
  | xargs sed -i \
    -e 's/tel:+917604029237/tel:+919876543210/g' \
    -e 's/+91 76040 29237/+91 98765 43210/g' \
    -e 's/917604029237/919876543210/g'
```

**Windows**, in VS Code: press `Ctrl+Shift+H` and do the same three
replacements, one after another, clicking "Replace All" each time.

This does not touch the WhatsApp number, which is separate — see
[section 1b](#b-the-whatsapp-number).

Afterwards, search the folder for the old digits `7604029237` to be sure none
are left, and check `llms.txt` and `sitemap.xml` by eye.

### Change the email address

Search for the old address and replace it everywhere, exactly as in
[section 1a](#a-the-firms-email-address).

### Change the chambers hours or address

Search for the printed text and replace it everywhere:

- hours: `9:00 AM to 9:00 PM` and `9:00 AM – 9:00 PM` (English),
  `प्रातः 9:00 से रात्रि 9:00` (Hindi), `সকাল ৯টা থেকে রাত ৯টা` (Bengali)
- address: `Ground Floor, 121/B Sitaram Ghosh Street, Kolkata 700009`

Two extra places to update by hand if the hours or address change:

1. The block near the top of each page that begins
   `<script type="application/ld+json">` — the `"opens"`, `"closes"` and
   `"streetAddress"` lines inside it. This is the machine-readable copy that
   Google uses for the map panel, so it must match the visible text.
2. `llms.txt`, which repeats the same facts for AI assistants.

### Replace a photo placeholder with a real photograph

1. Save the photograph as a `.jpg`, about 1200 pixels wide, and put it in a new
   folder called `assets/photos/`. Give it a simple name with no spaces, for
   example `bimlesh-kumar-jain.jpg`.
2. Open the page you want it on, and find the block that starts with
   `<figure class="photo">`. Just inside it there is a comment showing exactly
   what to paste, already filled in with the right file name and description:

   ```html
   <!-- PHOTO PLACEHOLDER. When the real photograph is ready, delete the <svg> below
        and paste this in its place ...
        <picture>
          <source srcset="/assets/photos/bimlesh-kumar-jain.webp" type="image/webp">
          <img src="/assets/photos/bimlesh-kumar-jain.jpg" width="1200" height="750"
               loading="lazy" decoding="async"
               alt="Photograph of Bimlesh Kumar Jain, B.A. LL.B., advocate at Civil Law Firm, Kolkata">
        </picture>
   -->
   ```

3. Delete the whole `<svg class="photo-ph"> ... </svg>` block, and paste in the
   `<picture>...</picture>` part from the comment.
4. If you have no `.webp` version of the picture, delete the `<source ...>`
   line. The `.jpg` alone works perfectly.
5. Change the `width` and `height` numbers to your picture's real size (this
   stops the page from jumping about while it loads).
6. Delete the `<figcaption class="photo-note">...</figcaption>` line, which is
   the "Photograph to be added" caption.
7. Repeat on the Hindi and Bengali copies of the same page. Use the same picture
   file; only the `alt="..."` description differs, and it is already translated
   in each file.

Keep the `alt="..."` description: it is what a blind visitor hears, and what
Google reads. It is already written for every placeholder.

### Add a blog post (an "Insight")

Posts keep the site alive in Google's eyes, and they are the easiest thing to
add. There are five steps, and about ten minutes of work.

**Step 1 — copy the template folder.** Copy the folder `insights/_template` and
paste it back into `insights/`, then rename the copy to a short description of
the post with hyphens instead of spaces, all lowercase. That name becomes the
web address. For example, renaming it to `how-partition-suits-work` gives
`civillawfirm.in/insights/how-partition-suits-work/`.

**Step 2 — edit the page.** Open the `index.html` inside your new folder and
change:

| Find this | Change it to |
| --- | --- |
| `<title>Post Title Goes Here \| Recents ...</title>` | the real title, about 60 characters |
| `<meta name="description" content="Replace this sentence ...">` | one sentence about the post, about 150 characters |
| `<link rel="canonical" href="https://civillawfirm.in/insights/_template/">` | `.../insights/how-partition-suits-work/` |
| the three `<link rel="alternate" hreflang=...>` lines | the same new folder name, keeping `/hi/` and `/bn/` |
| `<h1>Post title goes here</h1>` | the real title |
| `<p class="lede">Replace this with ...</p>` | a one-line summary |
| `2026-01-01` (appears twice — in `datetime="..."` and as the visible date) | today's date, as `2026-08-17` |
| `"headline"`, `"description"`, `"url"`, `"datePublished"`, `"dateModified"` inside the `<script type="application/ld+json">` block | the same title, description, address and date |
| the paragraphs between `<div class="post-body">` and `</div>` | the actual post, each paragraph wrapped in `<p>` and `</p>` |

Then **delete these two lines**, which exist only in the template:

```html
<meta name="robots" content="noindex, follow">
```
```html
<p class="template-note"><strong>TEMPLATE — this page is a copy-me stub ...</strong></p>
```

(The first one is what tells Google to ignore the template. If you leave it in,
your new post will never appear in search results.)

**Step 3 — list it on the Recents page.** Open `insights/index.html`, find the
list marked `<!-- ===== POST LIST ===== -->`, copy one whole block from
`<li class="post-item">` to `</li>`, paste it at the **top** of the list, and
change the date, title, address and summary in your copy. Newest posts go first.

**Step 4 — add it to the sitemap.** Open `sitemap.xml` and copy one existing
`<url>...</url>` block for an insight, paste it in, and change the three
addresses in it to your new post's addresses.

**Step 5 — do the same for Hindi and Bengali,** if the post is being translated:
copy `hi/insights/_template` and `bn/insights/_template` the same way, using the
**same folder name** as the English one. If a post exists only in English, then
in that post delete the two `<link rel="alternate" hreflang="hi" ...>` and
`hreflang="bn"` lines, and do not add Hindi and Bengali entries to the sitemap.

Keep every post **informational** — see
[section 6](#6-rules-that-must-not-be-broken-bar-council-of-india).

### Add a new practice-area page

1. Copy an existing folder such as `practice-areas/recovery-suits` and rename it,
   for example to `practice-areas/consumer-disputes`.
2. Open the `index.html` inside and change: the `<title>`, the
   `<meta name="description">`, the `<link rel="canonical">`, the three
   `hreflang` lines, the breadcrumb name, the `<h1>`, the lead paragraph, the
   body paragraphs, the "What this area covers" list, and the `"name"`,
   `"description"`, `"url"` and `"@id"` lines inside the
   `<script type="application/ld+json">` block.
3. In the "Related practice areas" list on the new page, remove the link to
   itself.
4. Add the new area to the list of cards on `practice-areas/index.html`: copy an
   existing `<li class="card">...</li>` block and change the address and words.
5. Add a link to it in the "Related practice areas" list of the other
   practice-area pages, and to the seven cards on the home page if you want it
   there.
6. Add the new area to the `"hasOfferCatalog"` list inside the JSON-LD block —
   the easiest way is to copy one of the seven entries already there.
7. Add three `<url>` blocks to `sitemap.xml` (English, Hindi, Bengali).
8. Repeat steps 1–5 in the `hi/` and `bn/` folders.
9. Mention the new area in `llms.txt`.

### Change the colours

Open `css/styles.css` and edit only the block at the very top, headed
`1. Colours, fonts and other settings`. Changing `--bg`, `--accent` and
`--ink` there changes the whole site at once. Nothing else needs touching.

---

## 6. Rules that must not be broken (Bar Council of India)

Advocates in India may not solicit work or advertise. This website has been
written to stay strictly on the right side of that rule, and anything added
later must do the same. Before publishing any new words, check them against
this list.

**You may state, as plain fact:** the firm's name; that it has practised since
1969; the chambers address and hours; the advocates' names, their B.A. LL.B.
qualification, their years in practice and their areas of work; the courts they
appear in; and the contact details.

**Never publish, anywhere, in any language:**

- Superlatives or comparisons — "best", "top", "leading", "No. 1", "renowned",
  "most experienced", "expert".
- Client testimonials, reviews, ratings, star ratings or endorsements.
- Case results, win rates, settlement figures, or any suggestion about how a
  matter will turn out.
- Awards, memberships or credentials the firm does not actually hold.
- Anything that reads as an invitation or inducement to engage the firm.

**When in doubt, make the sentence more factual and less flattering.** "The
chambers handles partition matters" is safe. "We are the people to call for
partition matters" is not.

Two further things to leave alone: the acknowledgement box that appears on a
first visit, and the `/disclaimer/` page. They are what record that the visitor
came looking of their own accord.

---

## 7. Things that keep the site findable — please do not remove them

Every page has a few lines near the top that do the quiet work of getting the
site into Google and into AI assistants. They look technical and are easy to
delete by accident.

| Line | What it does |
| --- | --- |
| `<title>` | the blue headline in Google results |
| `<meta name="description">` | the grey summary underneath it |
| `<link rel="canonical">` | says "this is the official address of this page", so Google does not treat two addresses as two pages |
| `<link rel="alternate" hreflang="...">` | tells Google that the English, Hindi and Bengali pages are the same page in three languages |
| `<script type="application/ld+json">` | the machine-readable copy of the firm's name, address, hours and phone numbers — this is what can produce the business panel beside search results |
| `<meta property="og:...">` | the picture and text shown when a link is shared on WhatsApp |
| `sitemap.xml` | the list of all pages, given to Google once |
| `robots.txt` | tells all crawlers, including AI ones, that they are welcome |
| `llms.txt` | a plain-language summary of the firm for AI assistants |

Three habits worth keeping:

1. **Every page needs its own `<title>` and description.** If you copy a page,
   change them. Two pages with the same title compete with each other.
2. **Never put words into a picture.** Text typed into the page can be read by
   Google; the same words inside a JPEG cannot.
3. **Do not make text appear only after a click, or only through JavaScript.**
   Everything important is written straight into the HTML on purpose. The
   acknowledgement box is deliberately built as a cover *on top of* a page that
   is already complete underneath, which is why the site remains fully readable
   to search engines. Keep it that way.

---

## 8. What every file and folder is for

```
/                             English site
  index.html                  Home
  about/                      The Firm
  practice-areas/             Listing + one folder per area (7)
  advocates/                  Listing + one folder per advocate (2)
  insights/                   Blog listing, the posts, and _template/
  contact/                    Contact page, with the enquiry form
  privacy-policy/  terms-of-use/  disclaimer/
  404.html                    Shown if someone types an address that is wrong

/hi/                          The same 21 pages, in Hindi
/bn/                          The same 21 pages, in Bengali

/css/styles.css               All the styling for the whole site
/js/main.js                   The only script: menu, acknowledgement box, form
/assets/                      favicon, logos, sharing picture
  favicon.svg                 the small icon in the browser tab
  logo-dark.svg               logo for light backgrounds (the one in use)
  logo-light.svg              logo for dark backgrounds, if ever needed
  og-image.png                picture shown when a link is shared
  og-image.svg                editable version of that picture
/assets/photos/               (create this) real photographs go here

sitemap.xml                   List of every page, for Google
robots.txt                    Permission for search engines and AI crawlers
llms.txt                      Plain-text summary of the firm for AI assistants
_headers                      Security and caching settings (Cloudflare/Netlify)
.nojekyll                     Needed by GitHub Pages
README.md                     This file
```

Every page carries the same header, footer, floating WhatsApp button and
acknowledgement box. Because these are plain HTML files, changing something in
the header means changing it in each file — use "Replace in Files" in VS Code,
or the Terminal commands shown above, rather than editing 67 files by hand.

---

## 9. Have the legal pages reviewed

The **Privacy Policy**, **Terms of Use** and **Disclaimer** pages have been
drafted to fit this website — a static site with no accounts, one email-only
contact form, and no tracking. They are a sound starting point, **not** advice,
and they have not been settled by anyone at the firm.

Please read all three, in all three languages, and change anything that does not
match how the firm actually works — particularly:

- how long enquiry emails are kept;
- who at the chambers receives them;
- whether any analytics or tracking is switched on later.

The same goes for the Hindi and Bengali translations throughout the site: they
have been written carefully and in matching legal register, but a first reading
by someone at the chambers who works in those languages every day is worth
doing before the site is announced.
