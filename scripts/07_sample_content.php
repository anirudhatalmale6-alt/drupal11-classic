<?php

/**
 * @file
 * Step 7 — taxonomy terms, demo users and sample content.
 *
 * Everything created here is placeholder copy written to exercise the layouts.
 * Delete it with: drush php:script scripts/99_purge_sample_content.php
 *
 * Run with: drush php:script scripts/07_sample_content.php
 */

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

// Relative to the Drupal root, so the build moves with the repository.
const CLASSIC_IMAGE_SOURCE = DRUPAL_ROOT . '/../assets/placeholders';

/**
 * Creates a term if one with the same name is not already in the vocabulary.
 */
function classic_term(string $vid, string $name): int {
  $existing = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
    ->loadByProperties(['vid' => $vid, 'name' => $name]);
  if ($existing) {
    return (int) reset($existing)->id();
  }
  $term = Term::create(['vid' => $vid, 'name' => $name]);
  $term->save();
  return (int) $term->id();
}

/**
 * Copies a placeholder JPEG into the public files directory and returns a file.
 */
function classic_image(string $slug): ?array {
  $source = CLASSIC_IMAGE_SOURCE . '/' . $slug . '.jpg';
  if (!is_file($source)) {
    // Loud rather than silent: an image field that quietly stays empty makes a
    // broken listing look like a design choice.
    echo "  !! missing placeholder image: $source\n";
    return NULL;
  }
  $directory = 'public://sample/' . date('Y-m');
  \Drupal::service('file_system')->prepareDirectory($directory, 1 | 2);
  $destination = $directory . '/' . $slug . '.jpg';

  $existing = \Drupal::entityTypeManager()->getStorage('file')
    ->loadByProperties(['uri' => $destination]);
  if ($existing) {
    $file = reset($existing);
  }
  else {
    $uri = \Drupal::service('file_system')->copy($source, $destination, 1);
    $file = File::create(['uri' => $uri, 'status' => 1]);
    $file->save();
  }
  return ['target_id' => $file->id(), 'alt' => ucwords(str_replace('-', ' ', preg_replace('/^(article|product|submission)-/', '', $slug)))];
}

/**
 * Creates a node unless one with the same title and type already exists.
 */
function classic_node(array $values): ?Node {
  $existing = \Drupal::entityTypeManager()->getStorage('node')
    ->loadByProperties(['type' => $values['type'], 'title' => $values['title']]);
  if ($existing) {
    echo "  = {$values['type']}: {$values['title']}\n";
    return reset($existing);
  }
  $node = Node::create($values);
  $node->save();
  echo "  + {$values['type']}: {$values['title']}\n";
  return $node;
}

/**
 * Wraps body text in the shape the text_with_summary field expects.
 */
function classic_body(string $html, string $summary = ''): array {
  return ['value' => $html, 'summary' => $summary, 'format' => 'basic_html'];
}

// ---------------------------------------------------------------------------
// Users.
// ---------------------------------------------------------------------------
echo "Users\n";
// The account name is what appears in a byline, so these are real-looking names
// rather than "editor" / "contributor". They are also the login names.
$demo_users = [
  'editor' => ['roles' => ['content_editor'], 'name' => 'Margaret Ashcombe', 'mail' => 'editor@example.com'],
  'contributor' => ['roles' => ['contributor'], 'name' => 'Tomas Iversen', 'mail' => 'contributor@example.com'],
];
$uids = [];
foreach ($demo_users as $key => $info) {
  $existing = user_load_by_name($info['name']) ?: user_load_by_name($key);
  if ($existing) {
    if ($existing->getAccountName() !== $info['name']) {
      $existing->setUsername($info['name'])->save();
      echo "  ~ renamed user to {$info['name']}\n";
    }
    $uids[$key] = $existing->id();
    echo "  = user {$info['name']}\n";
    continue;
  }
  $user = User::create([
    'name' => $info['name'],
    'pass' => 'Demo12345!',
    'mail' => $info['mail'],
    'status' => 1,
    'roles' => $info['roles'],
  ]);
  $user->save();
  $uids[$key] = $user->id();
  echo "  + user {$info['name']}\n";
}

// ---------------------------------------------------------------------------
// Taxonomy.
// ---------------------------------------------------------------------------
echo "Taxonomy\n";
$cat = [];
foreach (['Essays', 'Interviews', 'Notes', 'Reviews'] as $name) {
  $cat['article'][$name] = classic_term('article_category', $name);
}
foreach (['Ceramics', 'Textiles', 'Desk', 'Wood', 'Glass'] as $name) {
  $cat['product'][$name] = classic_term('product_category', $name);
}
foreach (['Kitchen table', 'Workshop', 'Letters'] as $name) {
  $cat['submission'][$name] = classic_term('submission_category', $name);
}
foreach (['craft', 'typography', 'repair', 'light', 'home', 'making'] as $name) {
  $cat['tags'][$name] = classic_term('tags', $name);
}
echo "  " . count($cat['article'] + $cat['product'] + $cat['submission'] + $cat['tags']) . " terms\n";

// ---------------------------------------------------------------------------
// Articles.
// ---------------------------------------------------------------------------
echo "Articles\n";
$articles = [
  [
    'title' => 'Hand-set type and the patience it asks for',
    'slug' => 'article-hand-set-type',
    'subtitle' => 'Setting a page by hand is slow in a way that turns out to be the point.',
    'category' => 'Essays',
    'tags' => ['typography', 'craft'],
    'minutes' => 7,
    'body' => <<<HTML
<p>There is a particular sound a composing stick makes when a line is nearly full: a dry click as the last sort drops in, and then the small silence while you decide whether the word will fit. It is not a sound anyone needs to hear any more. A page can be set in a browser in seconds, revised endlessly, and printed without a single piece of metal ever being lifted.</p>
<p>And yet the practice survives, in workshops and back rooms and a few stubborn university basements. It survives because the constraint is instructive. When every character has weight, you stop adding characters you do not need.</p>
<h2>The tyranny of the line</h2>
<p>A justified line in metal has to be made to fit. There is no algorithm to defer to. You add a hair space here, remove one there, and if it still will not sit you change the word. The text bends to the measure rather than the measure bending to the text.</p>
<p>Anyone who has spent a week doing this comes away writing shorter sentences. Not because short sentences are better, but because long ones cost more.</p>
<h2>What the screen borrowed</h2>
<p>Almost every typographic term still in use came out of this room. Leading was strips of lead. Uppercase and lowercase were the trays they lived in. We inherited the vocabulary and kept almost none of the discipline that produced it.</p>
<blockquote>A page set badly in a beautiful face will always be worse than a page set well in a plain one.</blockquote>
<p>The lesson is not that metal type is better. It is that the limits were doing work we have quietly stopped doing ourselves.</p>
HTML,
    'summary' => 'Setting a page in metal is slow, expensive, and unforgiving — which is precisely what makes it worth doing at least once.',
  ],
  [
    'title' => 'Quiet rooms',
    'slug' => 'article-quiet-rooms',
    'subtitle' => 'On the rooms we design to be unremarkable, and why they are the hardest ones.',
    'category' => 'Essays',
    'tags' => ['home', 'light'],
    'minutes' => 5,
    'body' => <<<HTML
<p>The rooms people love most are rarely the ones they photograph. They are the back bedroom with the good chair, the landing where the light lands at four o'clock, the kitchen corner with the wobbly table nobody has got round to fixing.</p>
<h2>Nothing to look at</h2>
<p>A quiet room is not an empty one. It is a room where nothing is competing. One good thing on a wall will hold a room; four will start an argument.</p>
<p>The discipline is subtractive, and it is uncomfortable, because removing something you paid for feels like waste. It is not. It is the only way the remaining things get to be seen.</p>
<h2>Light does most of the work</h2>
<p>Before buying anything, watch a room for a full day. Note where the light arrives and where it never reaches. Most rooms are arranged against their own light, out of habit rather than choice.</p>
<p>Then move one thing. Live with it for a week. Move one more.</p>
HTML,
    'summary' => 'The rooms people love most are rarely the ones they photograph.',
  ],
  [
    'title' => 'On repair',
    'slug' => 'article-on-repair',
    'subtitle' => 'A mended thing carries its history on the outside.',
    'category' => 'Notes',
    'tags' => ['repair', 'making'],
    'minutes' => 4,
    'body' => <<<HTML
<p>The first repair is the hardest, because it admits that the object was not perfect and now never will be. After that it gets easier. The second repair is almost a pleasure.</p>
<h2>Visible or invisible</h2>
<p>There are two schools. One hides the join and hopes you will not notice. The other draws attention to it, in gold or contrasting thread, and asks you to read the break as part of the object.</p>
<p>Neither is wrong. But the invisible repair fails badly — when you do eventually notice it, you feel deceived. The visible one can only ever surprise you pleasantly.</p>
<h2>What repair costs</h2>
<p>Usually more than replacement, in time if not in money. That arithmetic is the whole problem, and no amount of good intention fixes it. What does help is owning fewer things that are worth the trouble.</p>
HTML,
    'summary' => 'A mended thing carries its history on the outside, which is either a flaw or the whole point.',
  ],
  [
    'title' => 'Winter light',
    'slug' => 'article-winter-light',
    'subtitle' => 'Four months of low sun, and what to do with a room that only gets an hour of it.',
    'category' => 'Notes',
    'tags' => ['light', 'home'],
    'minutes' => 6,
    'body' => <<<HTML
<p>From November the sun never gets far above the rooftops, and the light that does arrive comes in almost horizontally. It reaches further into a room than summer light ever does, and it arrives the colour of weak tea.</p>
<h2>Work with the angle</h2>
<p>Low light rakes across surfaces instead of falling onto them. Texture that is invisible in June — the weave of a linen runner, the tool marks on a turned bowl — becomes the most interesting thing in the room.</p>
<p>This is the season for matte finishes and honest materials. Gloss just throws the light back at you.</p>
<h2>One warm source</h2>
<p>Resist the urge to light the whole room evenly. A single warm lamp, low down, does more for a winter evening than six ceiling spots ever will.</p>
HTML,
    'summary' => 'Low sun rakes across a room instead of falling into it, and that changes what is worth looking at.',
  ],
  [
    'title' => 'A short history of the margin',
    'slug' => 'article-a-short-history-of-the-margin',
    'subtitle' => 'Why the empty part of a page was never empty.',
    'category' => 'Essays',
    'tags' => ['typography', 'craft'],
    'minutes' => 8,
    'body' => <<<HTML
<p>For most of the history of the book, the margin was where the reading happened. Scribes left it wide on purpose. Readers filled it with arguments, corrections, and the occasional complaint about the author.</p>
<h2>The proportions</h2>
<p>The classical page put the text block off-centre: narrow at the spine, wider at the fore-edge, narrowest at the head, widest at the foot. The block sits slightly high and slightly inboard, which is why a well-made book feels settled in the hand.</p>
<p>Nothing about this is arbitrary. The eye reads a page as an object, and an object that sits too low looks like it is falling.</p>
<h2>What we lost</h2>
<p>Cheap paper killed the wide margin, and the screen finished it off. A web page has no fore-edge, no spine, and no natural limit — which means the only thing holding the measure is whoever wrote the stylesheet.</p>
<p>Forty ems is a reasonable line. Beyond about seventy-five characters the eye starts losing its place on the return, and no amount of leading rescues it.</p>
HTML,
    'summary' => 'For most of the history of the book, the empty part of the page was where the reading happened.',
  ],
  [
    'title' => 'The long table',
    'slug' => 'article-the-long-table',
    'subtitle' => 'In conversation with a maker who has built the same table for thirty years.',
    'category' => 'Interviews',
    'tags' => ['making', 'craft'],
    'minutes' => 9,
    'body' => <<<HTML
<p>The workshop is colder than expected and smells of oak dust and linseed. The table under discussion is three metres long and will not leave the building until a wall comes out.</p>
<h2>On making the same thing repeatedly</h2>
<p>"People assume it must be boring. It is the opposite. The first twenty were me finding out what the design wanted. The next fifty were me getting out of its way."</p>
<p>The proportions have barely moved in three decades. The joinery has changed twice, both times because a customer's floor was uneven and a rigid frame would have racked.</p>
<h2>On timber</h2>
<p>"I stopped specifying a grade years ago. You take the board you are given and you put the best face where people will put their elbows. Everything else is arrangement."</p>
<blockquote>A table is not finished when you stop working on it. It is finished about fifteen years later, when the top has gone the colour it was always going to go.</blockquote>
<h2>On the end of it</h2>
<p>There is no succession plan, and no interest in one. "Somebody will make tables. They will not be these tables, and that is completely fine."</p>
HTML,
    'summary' => 'Thirty years, one design, and a table that will not leave the workshop until a wall comes out.',
  ],
];

$created = 0;
foreach ($articles as $i => $article) {
  $node = classic_node([
    'type' => 'article',
    'title' => $article['title'],
    'uid' => $uids['editor'],
    'status' => 1,
    'moderation_state' => 'published',
    'created' => strtotime("-" . (($i + 1) * 9) . " days"),
    'field_subtitle' => $article['subtitle'],
    'field_article_category' => ['target_id' => $cat['article'][$article['category']]],
    'field_tags' => array_map(fn($t) => ['target_id' => $cat['tags'][$t]], $article['tags']),
    'field_reading_time' => $article['minutes'],
    'field_image' => classic_image($article['slug']),
    'body' => classic_body($article['body'], $article['summary']),
  ]);
  $created += $node ? 1 : 0;
}

// ---------------------------------------------------------------------------
// Products.
// ---------------------------------------------------------------------------
echo "Products\n";
$products = [
  ['Stoneware carafe', 'product-stoneware-carafe', 'CLS-CER-001', '48.00', 'in_stock', 'Ceramics', ['Stoneware', 'Matte glaze'], '24 cm high, 1.1 litre', 'Thrown in a single piece, glazed inside and out, and fired to a temperature that makes it genuinely dishwasher-safe. The foot is left unglazed so it sits quietly on a wooden surface.'],
  ['Linen table runner', 'product-linen-runner', 'CLS-TEX-004', '64.00', 'in_stock', 'Textiles', ['Washed linen', 'Hand-hemmed'], '45 × 200 cm', 'Heavyweight linen, washed twice before hemming so it arrives soft and stays flat. The colour shifts a little with each wash, which is the material behaving as it should.'],
  ['Brass desk rule', 'product-brass-desk-rule', 'CLS-DSK-002', '32.00', 'in_stock', 'Desk', ['Solid brass'], '30 cm', 'Milled from solid brass and engraved rather than printed, so the markings do not wear off. It will darken with handling. If you would rather it did not, a minute with a soft cloth puts it back.'],
  ['Oak reading stand', 'product-oak-reading-stand', 'CLS-WOD-007', '145.00', 'made_to_order', 'Wood', ['European oak', 'Hard wax oil'], '34 × 28 cm, three angles', 'Made to order in about four weeks. Three fixed angles, no hardware visible from the front, and a lip deep enough to hold a heavy book open without creasing the spine.'],
  ['Cotton throw', 'product-cotton-throw', 'CLS-TEX-011', '89.00', 'in_stock', 'Textiles', ['Organic cotton'], '130 × 180 cm', 'Woven on a narrow loom and finished with a hand-knotted fringe. Warm enough for a cold room, light enough to leave out in July.'],
  ['Glass decanter', 'product-glass-decanter', 'CLS-GLS-003', '72.00', 'out_of_stock', 'Glass', ['Mouth-blown glass'], '26 cm high, 750 ml', 'Mouth-blown, so no two are quite the same height. Small bubbles in the wall are a feature of the process rather than a fault.'],
  ['Ceramic bowl, set of four', 'product-ceramic-bowl-set', 'CLS-CER-009', '96.00', 'in_stock', 'Ceramics', ['Stoneware', 'Ash glaze'], '16 cm diameter each', 'Four bowls from the same firing, which means four slightly different results from the same glaze. Stackable, and sized for everything from breakfast to a serious soup.'],
  ['Leather portfolio', 'product-leather-portfolio', 'CLS-DSK-014', '210.00', 'made_to_order', 'Desk', ['Vegetable-tanned leather', 'Linen thread'], 'A4, 2 cm spine', 'Saddle-stitched by hand along the spine. Vegetable-tanned leather starts pale and ends up the colour of strong tea after a year of being carried.'],
  ['Enamel jug', 'product-enamel-jug', 'CLS-CER-020', '38.00', 'discontinued', 'Ceramics', ['Enamelled steel'], '18 cm high, 900 ml', 'The last of a run we are not repeating. Chips are inevitable with enamel and, in our view, improve it.'],
];

foreach ($products as $i => [$title, $slug, $sku, $price, $availability, $category, $materials, $dimensions, $description]) {
  classic_node([
    'type' => 'product',
    'title' => $title,
    'uid' => $uids['editor'],
    'status' => 1,
    'moderation_state' => 'published',
    'created' => strtotime("-" . (($i + 1) * 3) . " days"),
    'field_sku' => $sku,
    'field_price' => $price,
    'field_availability' => $availability,
    'field_product_category' => ['target_id' => $cat['product'][$category]],
    'field_materials' => array_map(fn($m) => ['value' => $m], $materials),
    'field_dimensions' => $dimensions,
    'field_product_image' => classic_image($slug),
    'body' => classic_body('<p>' . $description . '</p>'),
  ]);
}

// ---------------------------------------------------------------------------
// User-generated entries.
//
// Deliberately spread across moderation states so the queue has something in it
// on the first look.
// ---------------------------------------------------------------------------
echo "Community entries\n";
$submissions = [
  ['My grandmother\'s table', 'submission-grandmothers-table', 'Kitchen table', 'published', 'Elise Vandermeer', 'Ghent', '<p>It came out of a farmhouse near Oudenaarde and it has a burn mark shaped like Portugal where somebody in 1974 put a pan down without thinking. My mother tried to sand it out once and gave up after twenty minutes.</p><p>I have had it for eleven years now. Two of my children have done their homework on it. The burn is still there and I have stopped noticing it, except when someone new asks.</p>'],
  ['The bookbinder on Tanner Street', 'submission-the-bookbinder', 'Workshop', 'published', 'Ravi Chandrasekaran', 'London', '<p>He has been there since before the street had a coffee shop on it. You go in with a paperback that has lost its cover and he turns it over twice, quotes you a price that seems too low, and tells you to come back on Thursday.</p><p>On Thursday it is in quarter cloth with a hand-lettered spine and you cannot quite believe it is the same book.</p>'],
  ['A kitchen in May', 'submission-a-kitchen-in-may', 'Kitchen table', 'published', 'Hana Bergström', 'Malmö', '<p>Ours faces east, so the good light is all before nine in the morning and there is nothing to be done about that. For about forty minutes it comes in low across the table and everything on it looks better than it is.</p><p>We eat breakfast late on purpose, to catch the end of it.</p>'],
  ['Notes on a borrowed plane', 'submission-the-bookbinder', 'Workshop', 'needs_review', 'Peter Oyelaran', 'Bristol', '<p>A neighbour lent me a wooden jack plane that belonged to his father. It needed an hour of work on the sole and a proper sharpening before it would take a shaving.</p><p>I am writing this down mainly so I remember the order I did things in, because I will forget by the time I need it again.</p>'],
  ['The chair I should have thrown out', 'submission-grandmothers-table', 'Letters', 'draft', 'Anneke de Boer', 'Utrecht', '<p>Still writing this one. The short version is that the chair was free, it was broken, I fixed it badly, and now I like it more than the chairs I paid for.</p>'],
];

foreach ($submissions as $i => [$title, $slug, $category, $state, $contributor, $location, $html]) {
  classic_node([
    'type' => 'submission',
    'title' => $title,
    'uid' => $uids['contributor'],
    'moderation_state' => $state,
    'created' => strtotime("-" . (($i + 1) * 5) . " days"),
    'field_submission_category' => ['target_id' => $cat['submission'][$category]],
    'field_contributor_name' => $contributor,
    'field_contributor_location' => $location,
    'field_submission_image' => classic_image($slug),
    'body' => classic_body($html),
  ]);
}

// ---------------------------------------------------------------------------
// Basic pages.
// ---------------------------------------------------------------------------
echo "Pages\n";
$about = classic_node([
  'type' => 'page',
  'title' => 'About',
  'uid' => $uids['editor'],
  'status' => 1,
  'moderation_state' => 'published',
  'body' => classic_body(<<<HTML
<p>This is placeholder copy. Replace it with the real thing before launch.</p>
<p>Classic is a demonstration build: three content types, a moderation workflow for visitor submissions, and a theme built around traditional typography and a restrained palette.</p>
<h2>What is here</h2>
<ul>
<li><a href="/journal">The journal</a> — articles and blog posts.</li>
<li><a href="/catalogue">The catalogue</a> — product listings with availability and pricing.</li>
<li><a href="/community">From our readers</a> — entries sent in by visitors, published only after review.</li>
</ul>
HTML),
]);

$submit_page = classic_node([
  'type' => 'page',
  'title' => 'Send us an entry',
  'uid' => $uids['editor'],
  'status' => 1,
  'moderation_state' => 'published',
  'body' => classic_body(<<<HTML
<p>We publish entries from readers. Nothing appears on the site until an editor has read it.</p>
<h2>How it works</h2>
<ol>
<li>Create an account. New accounts are approved by hand, so there is a short wait.</li>
<li>Write your entry and choose <em>Submit for review</em> when you are happy with it.</li>
<li>An editor reads it and either publishes it or sends it back with a note.</li>
</ol>
<p><a class="button" href="/node/add/submission">Write an entry</a></p>
<h2>What we are looking for</h2>
<p>Short pieces about a thing you own, made, mended, or inherited. Three hundred words is plenty. One photograph is plenty.</p>
HTML),
]);

// ---------------------------------------------------------------------------
// Menu links for the two pages.
// ---------------------------------------------------------------------------
echo "Menu links\n";
foreach ([['About', $about, 4], ['Send us an entry', $submit_page, 5]] as [$title, $node, $weight]) {
  if (!$node) {
    continue;
  }
  $uri = 'entity:node/' . $node->id();
  $existing = \Drupal::entityTypeManager()->getStorage('menu_link_content')
    ->loadByProperties(['menu_name' => 'main', 'link.uri' => $uri]);
  if ($existing) {
    echo "  = menu link: $title\n";
    continue;
  }
  \Drupal\menu_link_content\Entity\MenuLinkContent::create([
    'title' => $title,
    'link' => ['uri' => $uri],
    'menu_name' => 'main',
    'weight' => $weight,
    'expanded' => FALSE,
  ])->save();
  echo "  + menu link: $title\n";
}

echo "Done.\n";
