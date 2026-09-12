# Running the site

A short guide for whoever looks after the content. No command line needed.

---

## Logging in

`/user/login`. Editors land on the content list; there is a sidebar on the left
for Create, Content, Files and Media.

## Writing an article

**Create → Article.**

| Field | Notes |
|---|---|
| Title | Also becomes the URL, e.g. `/journal/winter-light` |
| Standfirst | One sentence under the headline. Optional but it carries the teaser |
| Category | Essays, Interviews, Notes, Reviews — add more under Structure → Taxonomy |
| Tags | Free text, comma separated. New tags are created as you type |
| Image | Shown at the top of the article and in the listing. Alt text is required |
| Description | The article itself |
| Reading time | Stored but not displayed. There if you want it later |

At the bottom, **Save as** chooses the state:

- **Draft** — saved, not public.
- **Published** — live.
- **Archived** — taken off the site but kept.

A published article can be edited and saved as Draft; the live version stays up
until you publish the new one. The **Latest version** tab on the article shows
the unpublished draft.

## Adding a product

**Create → Product Listing.** SKU, Price and Availability are required.

Availability drives the badge on the catalogue card: In stock, Made to order,
Out of stock, Discontinued. Add or rename options under Structure → Content
types → Product Listing → Manage fields → Availability.

Up to three images; the first is the one the catalogue card uses. Materials
takes up to five entries, one per line.

Prices show `£`. To change the currency: Structure → Content types → Product
Listing → Manage fields → Price → Prefix.

## Visitor entries

Visitors write these; you decide what appears.

**Everything waiting on you is at Content → Moderation queue**
(`/admin/content/queue`).

Open an entry and use the moderation control at the bottom:

| Action | What happens |
|---|---|
| **Approve and publish** | It goes live on `/community` |
| **Send back to the contributor** | Back to Draft. They can edit and resubmit |
| **Reject / unpublish** | Off the site. They can still revise and resubmit |

Rejecting something already published takes it down — that is also how you
unpublish an entry.

The contributor's name and location come from fields they fill in, not from
their account, so someone can be credited however they like.

## Letting people submit

By default a visitor must **register first**, and new accounts need your
approval — Manage → People → the account → Activate. This is the setting that
keeps spam out.

To let anyone submit without an account: Manage → People → Permissions, and for
the **Anonymous user** column tick:

- User-Generated Entry: Create new content
- Community submission workflow: Use *Submit for review* transition

The form is protected by a honeypot either way, and nothing published happens
without you.

If someone complains the form rejected them with "please wait N seconds", that
is the honeypot's timer: the form was submitted within five seconds of loading.
Genuine writing takes longer, so in practice it only catches bots — but if it
becomes a nuisance, raise or remove the limit at
Configuration → Content authoring → Honeypot.

## Menus

Manage → Structure → Menus → Main navigation. Drag to reorder. The Journal,
Catalogue and Community items come from the Views and are edited there
(Structure → Views → *view* → Page → Menu).

## The front page

It is the Journal listing with a hero above it.

- Hero text: Structure → Block layout → Custom block library → *Front page hero*.
- What appears below: Structure → Views → Journal.

## Images

Upload straight onto the content. Alt text is required, and it should describe
the image rather than repeat the headline.

The current images are placeholders — abstract shapes, not photographs. Replace
them with real pictures; no resizing needed, the site generates its own sizes.

## Housekeeping

- **Reports → Status report** — check monthly. Green is the goal.
- **Reports → Recent log messages** — where errors surface.
- **Reports → Available updates** — security updates should be applied
  promptly. Ask your developer rather than clicking Update through the browser.
- **Content → Files** — orphaned uploads accumulate; they are safe to delete if
  nothing references them.
