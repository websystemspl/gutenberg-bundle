# GutenbergBundle

The WordPress block editor (Gutenberg) as a Symfony form field.

Add one field to a form and editors get the real block editor — the upstream
`@wordpress/block-editor` packages, not a re-implementation. Content is stored as ordinary
block markup, rendered on the front end with one Twig call, and extended with custom blocks
that are written entirely in PHP.

```php
$builder->add('content', GutenbergType::class);
```

```twig
{{ gutenberg_render(page.content) }}
```

## Why block markup

The field's value is the same string WordPress stores: HTML annotated with block delimiters.

```html
<!-- wp:heading --><h2>Hello</h2><!-- /wp:heading -->
<!-- wp:app/hero {"title":"Welcome"} /-->
```

Static blocks carry their own markup, so rendering them costs nothing on the server. Dynamic
blocks store attributes only and are rendered by PHP at request time. Content stays portable:
it can be moved to or from WordPress unchanged.

## Installation

```bash
composer require web-systems/gutenberg-bundle
```

Register the bundle (Flex does this for you) and import the routes it needs for server-side
previews, media and translations:

```yaml
# config/routes/web_systems_gutenberg.yaml
web_systems_gutenberg:
    resource: '@WebSystemsGutenbergBundle/config/routes.php'
    prefix: /_gutenberg
```

Publish the editor assets:

```bash
bin/console assets:install public
```

If Doctrine is installed, register the bundle's migrations and run them — they create the
revision and reusable-block tables:

```yaml
# config/packages/doctrine_migrations.yaml
doctrine_migrations:
    migrations_paths:
        'WebSystems\GutenbergBundle\Migrations': '%kernel.project_dir%/vendor/web-systems/gutenberg-bundle/migrations'
```

```bash
bin/console doctrine:migrations:migrate
```

The bundle works without Doctrine too; revisions and reusable blocks are then simply absent.

## Using the field

### Plain Symfony forms

```php
$builder->add('content', GutenbergType::class, [
    'editor_height' => 800,
    'allowed_blocks' => ['core/paragraph', 'core/heading', 'core/image'],
    'block_template' => [['core/heading', ['level' => 2]], ['core/paragraph', []]],
    'template_lock' => 'insert',
]);
```

The field is a `TextareaType` underneath: the value stays a plain string, validation and
persistence work as usual, and the form degrades to a textarea when JavaScript is unavailable.
The editor bundle is attached automatically, so a hand-written admin panel needs no extra work.

### EasyAdmin

```php
use WebSystems\GutenbergBundle\EasyAdmin\Field\GutenbergField;

yield GutenbergField::new('content', 'Page content')
    ->setHeight(760)
    ->renderOnDetail();
```

The field attaches its own CSS and JS, so `configureAssets()` stays untouched. Register the
form theme in the CRUD controller so the widget is picked up:

```php
public function configureCrud(Crud $crud): Crud
{
    return $crud->setFormThemes([
        '@WebSystemsGutenberg/form/gutenberg_widget.html.twig',
        '@EasyAdmin/crud/form_theme.html.twig',
    ]);
}
```

## Rendering on the front end

```twig
{{ gutenberg_front_assets() }}          {# stylesheets for core blocks #}

{{ gutenberg_render(page.content) }}    {# the blocks #}
{{ gutenberg_render(page.content, {currentPageId: page.id}) }}   {# with render context #}

{{ gutenberg_excerpt(page.content, 160) }}
{% if gutenberg_has_blocks(page.content) %}…{% endif %}
{% for block in gutenberg_blocks(page.content) %}{{ block.name }}{% endfor %}
```

In PHP, depend on `ContentRendererInterface`:

```php
public function __construct(private ContentRendererInterface $renderer) {}

$html = $this->renderer->render($page->getContent());
```

## Custom blocks

A custom block is one PHP class and one Twig template. There is no JavaScript to write and no
asset to rebuild: the editor reads the field schema at runtime and generates the inspector
panel, while the canvas shows the real server-rendered output.

```bash
bin/console make:gutenberg-block Hero
```

```php
#[AsBlock(
    name: 'app/hero',
    title: 'Hero',
    icon: 'cover-image',
    category: 'cms',
    template: 'blocks/hero.html.twig',
)]
final class HeroBlock extends AbstractBlockType
{
    public function configureAttributes(AttributeBuilder $builder): void
    {
        $builder
            ->richText('title', 'Heading', 'Welcome')
            ->textarea('lead', 'Intro text')
            ->image('background', 'Background image')
            ->color('overlay', 'Overlay colour', '#1d3557')
            ->range('overlayOpacity', 'Overlay strength (%)', 55, 0, 100, 5)
            ->select('align', 'Alignment', ['left' => 'Left', 'center' => 'Centre'], 'center')
            ->url('cta', 'Button');
    }
}
```

```twig
{# templates/blocks/hero.html.twig #}
<section class="c-hero" style="background-color: {{ attributes.overlay }}">
    <h1>{{ attributes.title|raw }}</h1>
    {% if attributes.cta.url is defined %}<a href="{{ attributes.cta.url }}">{{ attributes.cta.label }}</a>{% endif %}
</section>
```

Available field types: `text`, `textarea`, `richText`, `html`, `number`, `range`, `toggle`,
`select`, `color`, `image`, `url`. Pass `inContent: true` to edit a field directly on the
canvas instead of in the sidebar.

Blocks are ordinary services, so they may inject anything:

```php
public function __construct(private PageRepository $pages) {}

public function render(BlockRenderContext $context): string
{
    return $this->getTwig()->render($this->getTemplate(), [
        'attributes' => $context->attributes,
        'pages' => $this->pages->findPublished((int) $context->get('limit', 3)),
    ]);
}
```

Set `innerBlocks: true` on `#[AsBlock]` to let editors nest other blocks inside; the rendered
children arrive in the template as `inner`.

Inspect what is registered:

```bash
bin/console debug:gutenberg-blocks
bin/console debug:gutenberg-blocks app/hero
```

## Theme colours and typography

The editor has no theme of its own: the palette, the gradients and the font sizes it offers all
come from configuration, and reach both the core blocks and the controls of your own blocks.

```yaml
web_systems_gutenberg:
    editor:
        palette:
            - { name: 'Brand',   slug: 'brand',   color: '#1d3557' }
            - { name: 'Accent',  slug: 'accent',  color: '#e63946' }
        gradients:
            # Double every % sign: Symfony reads a single one as a container parameter.
            - { name: 'Deep', slug: 'deep', gradient: 'linear-gradient(135deg, #1d3557 0%%, #457b9d 100%%)' }
        font_sizes:
            - { name: 'Large', slug: 'large', size: '24px' }
        custom_colors: true    # false restricts editors to the palette above
```

Colours land in the editor as a theme palette (`__experimentalFeatures.color.palette.theme`),
which is what the Colour panel of core blocks and the `color()` field of custom blocks both
read.

Picking a preset does not write the value into the markup — Gutenberg writes a class:

```html
<p class="has-accent-background-color has-background">…</p>
```

The bundle therefore generates the stylesheet that gives those classes meaning, the same
custom properties and `!important` preset rules WordPress builds from theme.json. It is emitted
by `gutenberg_front_assets()` and injected into the editor canvas, so a colour chosen while
editing is the colour a visitor sees. Set `preset_styles: false` if your own stylesheet already
defines `has-*-color`, `has-*-background-color`, `has-*-border-color`,
`has-*-gradient-background` and `has-*-font-size`. WordPress' own default palette is deliberately absent — it lives in the theme.json that a
WordPress install provides and this bundle does not — so what you configure is exactly what
editors see. Set `custom_colors: false` to remove the free colour picker and keep documents on
brand.

## Reusable blocks

A reusable block is a fragment saved once and referenced from many documents; editing it
updates every page that uses it. It is stored the way WordPress stores a synced pattern, so the
markup travels between the two systems unchanged:

```html
<!-- wp:block {"ref":7} /-->
```

### Creating one from the editor

Select the blocks to reuse, open the block's **Options** menu (⋮) and choose **Create
pattern**. The selection is saved under the name you give it and replaced in place by a
reference, so the document ends up holding `<!-- wp:block {"ref":N} /-->` and the new block is
immediately available in the inserter.

### Managing them

With Doctrine and EasyAdmin installed the bundle registers a management screen at
`/admin/reusable-block` automatically. Saved blocks appear in the editor's inserter under their
own category, each by name, and the canvas previews them server-side.

Turn the built-in screen off when your application ships its own — EasyAdmin refuses two CRUD
controllers that share a short class name:

```yaml
web_systems_gutenberg:
    easyadmin:
        reusable_block_crud: false
```

Applications without EasyAdmin drive `ReusableBlockRepository` from their own panel; the entity
is `WebSystems\GutenbergBundle\Entity\ReusableBlock` with a `title`, a `slug` and `content`.
References resolve by numeric id or by slug, and a block that references itself, directly or
through another one, is stopped by the render-depth guard rather than looping.

## Interface language

The editor ships in English and is translated with the official WordPress.org language packs.

```bash
bin/console gutenberg:translations pl_PL
bin/console gutenberg:translations --list
```

Block titles, descriptions and category names are translated as well: their source is
`block.json`, which WordPress translates while building its registry, so the bundle passes them
through the catalogue as each block registers.

The catalogue is written to `var/gutenberg/translations/` and served to the editor over a
cacheable endpoint. The locale defaults to `%kernel.default_locale%`; set
`web_systems_gutenberg.editor.locale` to override it. Right-to-left locales are detected
automatically, but you must point `assets.editor_styles` at `editor-rtl.css` yourself.

## Keeping the editor up to date

The editor is built from published `@wordpress/*` packages.

```bash
bin/console gutenberg:update             # report outdated packages
bin/console gutenberg:update --update    # pin the latest versions
bin/console gutenberg:update --build     # reinstall and rebuild public/editor.js
```

Rebuilding requires Node.js and is only needed by whoever maintains the package; installing
the bundle from Packagist ships the built assets.

## Extension points

Every part of the pipeline is an interface with a tagged implementation, so behaviour is added
by registering a service rather than by patching the bundle.

| Interface | Purpose |
| --- | --- |
| `BlockTypeInterface` | a server-rendered block; register with `#[AsBlock]` |
| `BlockRendererInterface` | a rendering strategy for a family of blocks, priority-ordered |
| `ContentRendererInterface` | the composite renderer consumers depend on |
| `EditorSettingsProviderInterface` | contributes a slice of the editor configuration |
| `MediaStorageInterface` | where uploaded images live |
| `RevisionStorageInterface` | how content snapshots are kept |
| `ReusableBlockProviderInterface` | resolves `core/block` references |
| `ReusableBlockWriterInterface` | stores a selection saved from the editor |
| `TranslationCatalogueInterface` | supplies the interface message map |
| `EditorAccessCheckerInterface` | guards the editor's endpoints |

## Configuration reference

```yaml
web_systems_gutenberg:
    editor:
        height: 720
        locale: '%kernel.default_locale%'
        content_width: '840px'         # width of the content column inside the canvas
        wide_width: '1140px'           # width of "wide" aligned blocks; null disables them
        canvas_padding_block: '44px'   # breathing room above and below the canvas content
        canvas_padding_inline: '28px'  # …and beside it; full-width blocks escape this gutter
        allowed_blocks: []
        canvas_styles: ['/css/front.css']   # your theme, appended to assets.canvas_styles
        custom_colors: true
        preset_styles: true         # generate CSS for the has-*-color classes the editor writes
        palette:
            - { name: 'Brand', slug: 'brand', color: '#1d3557' }
        gradients:
            - { name: 'Deep', slug: 'deep', gradient: 'linear-gradient(135deg,#1d3557,#457b9d)' }
        font_sizes:
            - { name: 'Large', slug: 'large', size: '24px' }
        categories:
            - { slug: 'cms', title: 'CMS blocks' }

    assets:
        editor_styles: ['bundles/websystemsgutenberg/editor.css']
        editor_scripts: ['bundles/websystemsgutenberg/editor.js']
        front_styles: ['bundles/websystemsgutenberg/front.css']
        # Loaded inside the canvas iframe, which is a separate document: without these the
        # in-canvas UI (appenders, placeholders, the Columns layout picker) is unstyled.
        canvas_styles:
            - 'bundles/websystemsgutenberg/canvas.css'
            - 'bundles/websystemsgutenberg/front.css'

    media:
        enabled: true
        directory: '%kernel.project_dir%/public/uploads/gutenberg'
        public_prefix: '/uploads/gutenberg'
        max_file_size: 8388608
        allowed_mime_types: ['image/jpeg', 'image/png', 'image/webp']

    translations:
        enabled: true
        directory: '%kernel.project_dir%/var/gutenberg/translations'
        http_cache_max_age: 86400

    revisions:
        enabled: true
        limit: 20

    easyadmin:
        reusable_block_crud: true   # register the built-in reusable-block CRUD screen

    security:
        access_role: ~          # e.g. ROLE_ADMIN

    max_render_depth: 10
    doctrine: ~                 # null auto-detects DoctrineBundle
```

## Security

The endpoints under the imported prefix (`/_gutenberg` by convention) render blocks, list and
accept media, and expose the editor configuration. Put them behind your firewall:

```yaml
security:
    access_control:
        - { path: ^/_gutenberg, roles: ROLE_ADMIN }
```

Setting `web_systems_gutenberg.security.access_role` adds a second check inside the bundle, for
defence in depth or for applications that do not use `access_control`.

Block content is trusted HTML, exactly as in WordPress: whoever can use the editor can emit
markup. Restrict the editor to trusted roles, or narrow `editor.allowed_blocks`.

## License

MIT.
