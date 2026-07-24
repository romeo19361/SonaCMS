<?php
// /SonaCMS/app/editor.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/auth.php';   // enforces login, starts session, provides csrf_token
require __DIR__ . '/paths.php';
require __DIR__ . '/functions.php';

$isNew = !isset($_GET['file']) && !isset($_POST['original_filename']);
$message = null;
$error = null;

// Default empty page
$page = [
    'filename' => '',
    'nav_label' => '',
    'show_in_nav' => true,
    'page_order' => 99,
    'title' => '',
    'slug' => '',
    'page_parent' => '',
    'content' => '',
    'meta_description' => '',
    'meta_keywords' => '',
    'og_image' => '',
    'hero_image' => '',
    'hero_title' => '',
    'hero_subtitle' => '',
    'date' => date('Y-m-d'),
    'show_date' => false,
    'status' => 'draft',
];

// Load existing page on GET
if (isset($_GET['file'])) {
    $existing = getPage($_GET['file']);
    if ($existing) {
        $page = array_merge($page, $existing);
        $isNew = false;
    } else {
        $error = "Page not found.";
        $isNew = true;
    }
}

// All pages, used to populate the parent dropdown (excluding self, to prevent self-parenting)
$allPages = getAllPages();

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security check failed. Please try again.";
    } else {
        // Lowercase the filename (the on-disk identifier and URL parent ref).
        // original_filename is lowercased the same way so rename-detection still
        // compares like-for-like.
        $filename = strtolower(trim($_POST['filename'] ?? ''));
        $originalFilename = strtolower(trim($_POST['original_filename'] ?? ''));

        $page['nav_label'] = trim($_POST['nav_label'] ?? '');
        $page['show_in_nav'] = isset($_POST['show_in_nav']);
        $page['page_order'] = max(0, (int)($_POST['page_order'] ?? 99));
        $page['title'] = trim($_POST['title'] ?? '');
        // Force slug to lowercase — URLs are case-sensitive on most (Linux)
        // servers, so storing a consistent lowercase slug avoids /About vs
        // /about resolving to different pages (or 404s on the "wrong" case).
        $page['slug'] = strtolower(trim($_POST['slug'] ?? ''));
        $page['page_parent'] = strtolower(trim($_POST['page_parent'] ?? ''));
        $page['content'] = $_POST['content'] ?? '';
        $page['meta_description'] = trim($_POST['meta_description'] ?? '');
        $page['meta_keywords'] = trim($_POST['meta_keywords'] ?? '');
        $page['og_image'] = trim($_POST['og_image'] ?? '');
        $page['hero_image'] = trim($_POST['hero_image'] ?? '');
        $page['hero_title'] = trim($_POST['hero_title'] ?? '');
        $page['hero_subtitle'] = trim($_POST['hero_subtitle'] ?? '');
        $page['date'] = trim($_POST['date'] ?? date('Y-m-d'));
        $page['show_date'] = isset($_POST['show_date']);
        $page['status'] = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
        $page['filename'] = $filename;

        if ($filename === '') {
            $error = "Filename is required.";
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $filename)) {
            $error = "Filename can only contain letters, numbers, hyphens and underscores.";
        } elseif ($page['slug'] === '') {
            $error = "Slug is required.";
        } elseif ($page['page_parent'] === $filename) {
            $error = "A page cannot be its own parent.";
        } else {
            // If renaming an existing file, remove the old one after a successful save
            $saveData = $page;
            if (savePage($filename, $saveData)) {
                if ($originalFilename !== '' && $originalFilename !== $filename) {
                    deletePage($originalFilename);
                }
                header('Location: admin.php');
                exit;
            } else {
                $error = "Could not save the page. Check folder permissions.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <title><?php echo $isNew ? 'New Page' : 'Edit Page'; ?> | CMS</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="sona-admin">
<div class="sona-wrap">

    <div class="sona-top-bar">
        <h2><?php echo $isNew ? 'New Page' : 'Edit Page'; ?></h2>
        <a href="admin.php">&larr; Back to list</a>
    </div>

    <?php if ($error): ?>
        <p class="sona-error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="original_filename" value="<?php echo htmlspecialchars($page['filename']); ?>">

        <div class="sona-form-columns">

            <div class="sona-form-col">

                <label class="sona-label" for="filename">Filename</label>
                <input class="sona-input" type="text" id="filename" name="filename" value="<?php echo htmlspecialchars($page['filename']); ?>" required>
                <p class="sona-hint">No spaces or extension — e.g. "about-us". This is the actual file on disk, not the URL.</p>

                <label class="sona-label" for="nav_label">Navigation Label</label>
                <input class="sona-input" type="text" id="nav_label" name="nav_label" value="<?php echo htmlspecialchars($page['nav_label']); ?>">
                <p class="sona-hint">Short text shown in site navigation menus — can differ from the full page title below (e.g. "Coffee Sourcing" vs. a longer SEO title).</p>

                <label class="sona-label" for="slug">Slug</label>
                <input class="sona-input" type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($page['slug']); ?>" required>
                <p class="sona-hint">Used to build the page URL, e.g. "about-us" or "blog/my-first-post".</p>

                <label class="sona-checkbox-row" for="show_in_nav">
                    <input type="checkbox" id="show_in_nav" name="show_in_nav" value="1" <?php echo !empty($page['show_in_nav']) ? 'checked' : ''; ?>>
                    Show in navigation
                </label>
                <p class="sona-hint">Uncheck to keep this page live and SEO-indexed, but hidden from menus (e.g. a checkout confirmation or legal page).</p>

                <label class="sona-label" for="page_parent">Parent Page</label>
                <select class="sona-select" id="page_parent" name="page_parent">
                    <option value="">— None (top level) —</option>
                    <?php foreach ($allPages as $candidate): ?>
                        <?php if ($candidate['filename'] === $page['filename']) continue; // can't be its own parent ?>
                        <option value="<?php echo htmlspecialchars($candidate['filename']); ?>" <?php echo $page['page_parent'] === $candidate['filename'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($candidate['title'] ?? $candidate['filename']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="sona-hint">Optional — nest this page under another, e.g. a blog post under a "Blog" parent page.</p>

                <label class="sona-label" for="page_order">Page Order</label>
                <input class="sona-input" type="number" id="page_order" name="page_order" min="0" max="999" value="<?php echo (int)($page['page_order'] ?? 99); ?>">
                <p class="sona-hint">Controls the order of this page in navigation menus. Lower numbers appear first. Default is 99.</p>

            </div>

            <div class="sona-form-col">

                <label class="sona-label" for="title">Title</label>
                <input class="sona-input" type="text" id="title" name="title" value="<?php echo htmlspecialchars($page['title']); ?>" required>

                <label class="sona-label" for="meta_description">Meta Description</label>
                <textarea class="sona-input sona-textarea" id="meta_description" name="meta_description" rows="4"><?php echo htmlspecialchars($page['meta_description']); ?></textarea>
                <p class="sona-hint">Aim for 150–160 characters — shown in search engine results below the page title.</p>

                <label class="sona-label" for="meta_keywords">Meta Keywords</label>
                <input class="sona-input" type="text" id="meta_keywords" name="meta_keywords" value="<?php echo htmlspecialchars($page['meta_keywords']); ?>">

                <label class="sona-label" for="og_image_file">Social Share Image</label>
                <div class="sona-og-uploader">
                    <input class="sona-input" type="file" id="og_image_file" accept="image/*">
                    <input type="hidden" id="og_image" name="og_image" value="<?php echo htmlspecialchars($page['og_image']); ?>">
                    <div class="sona-og-preview" id="og_image_preview">
                        <?php if (!empty($page['og_image'])): ?>
                            <img src="<?php echo htmlspecialchars($page['og_image']); ?>" alt="Social share preview">
                        <?php endif; ?>
                    </div>
                    <button type="button" class="sona-og-clear" id="og_image_clear"<?php echo empty($page['og_image']) ? ' style="display:none;"' : ''; ?>>Remove image</button>
                </div>
                <p class="sona-hint">Shown when the page is shared on X, Facebook, LinkedIn, etc. Best size is <strong>1200 × 630px</strong>. Keep key text and logos in the centre — some platforms crop the edges. Leave blank to share with no preview image.</p>

            </div>

        </div>

        <label class="sona-label" for="hero_image_file">Hero Image</label>
        <div class="sona-og-uploader">
            <input class="sona-input" type="file" id="hero_image_file" accept="image/*">
            <input type="hidden" id="hero_image" name="hero_image" value="<?php echo htmlspecialchars($page['hero_image']); ?>">
            <div class="sona-og-preview" id="hero_image_preview">
                <?php if (!empty($page['hero_image'])): ?>
                    <img src="<?php echo htmlspecialchars($page['hero_image']); ?>" alt="Hero image preview">
                <?php endif; ?>
            </div>
            <button type="button" class="sona-og-clear" id="hero_image_clear"<?php echo empty($page['hero_image']) ? ' style="display:none;"' : ''; ?>>Remove image</button>
        </div>
        <p class="sona-hint">Optional banner image shown at the top of the page, with the title and subtitle below overlaid on top. Recommended size depends on your frontend design — ask your developer. Leave blank for no hero banner.</p>

        <label class="sona-label" for="hero_title">Hero Title</label>
        <input class="sona-input" type="text" id="hero_title" name="hero_title" value="<?php echo htmlspecialchars($page['hero_title']); ?>">

        <label class="sona-label" for="hero_subtitle">Hero Subtitle</label>
        <input class="sona-input" type="text" id="hero_subtitle" name="hero_subtitle" value="<?php echo htmlspecialchars($page['hero_subtitle']); ?>">
        <p class="sona-hint">The title and subtitle overlay the hero image. Both are optional.</p>

        <label class="sona-label" for="content">Content</label>
        <div id="editorjs" class="sona-editorjs"></div>
        <input type="hidden" id="content" name="content" value="">
        <p class="sona-hint">Click to start writing. Use the "+" handle on the left of each block, or the toolbox icon, to add headers, lists, quotes, etc.</p>

        <label class="sona-label" for="date">Date</label>
        <input class="sona-input" type="date" id="date" name="date" value="<?php echo htmlspecialchars($page['date']); ?>">

        <label class="sona-checkbox-row" for="show_date">
            <input type="checkbox" id="show_date" name="show_date" value="1" <?php echo !empty($page['show_date']) ? 'checked' : ''; ?>>
            Show publish date on the page
        </label>
        <p class="sona-hint">Displays the date above the content — useful for blog posts and news. Off by default.</p>

        <label class="sona-label" for="status">Status</label>
        <select class="sona-select" id="status" name="status">
            <option value="draft" <?php echo $page['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
            <option value="published" <?php echo $page['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
        </select>

        <button class="sona-btn sona-btn--block" type="submit">Save Page</button>
    </form>

</div>

<!-- Editor.js core + tools — pinned to specific versions (not @latest) to
     avoid silent breakage when independently-versioned packages drift out
     of sync with each other. -->
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2.31.6"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@2.8.9"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@2.0.9"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/paragraph@2.11.7"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/quote@2.7.6"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/underline@1.2.1"></script>
<script src="../vendor/image-tool.js"></script>
<script src="../vendor/gallery-tool.js"></script>
<script src="../vendor/download-tool.js"></script>
<script src="../vendor/section-tool.js"></script>
<script src="../vendor/map-tool.js"></script>
<script src="../vendor/facebook-tool.js"></script>
<script src="../vendor/tile-tool.js"></script>
<script src="../vendor/pricing-tool.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@calumk/editorjs-columns@0.3.2"></script>
<script src="../vendor/button-tool.js"></script>
<script src="../vendor/form-tool.js"></script>
<script src="../vendor/author-tool.js"></script>
<script src="../vendor/alignment-tune.js"></script>
<script src="../vendor/code-tool.js"></script>
<script src="../vendor/video-embed-tool.js"></script>
<script src="../vendor/emoji-inline-tool.js"></script>

<script>
    // List of available forms from /forms/, injected server-side.
    // The FormTool reads this to populate its dropdown.
    window.SONA_FORMS = <?php echo json_encode(getAvailableForms()); ?>;

    // List of available authors, injected server-side.
    // The AuthorTool reads this to populate its dropdown.
    window.SONA_AUTHORS = <?php
    echo json_encode(array_map(function ($a) {
        return ['filename' => $a['filename'], 'name' => $a['name'] ?? $a['filename']];
    }, getAllAuthors()));
    ?>;
</script>

<script>
    // Existing content from PHP. Older pages may have plain text/HTML saved
    // as a string (from before Editor.js was added) rather than block JSON —
    // detect and convert that case so nothing breaks on existing content.
    const rawContent = <?php echo json_encode($page['content']); ?>;
    let initialData = { blocks: [] };

    if (rawContent) {
        try {
            const parsed = JSON.parse(rawContent);
            if (parsed && Array.isArray(parsed.blocks)) {
                initialData = parsed;
            } else {
                throw new Error('Not block-format JSON');
            }
        } catch (e) {
            // Legacy plain text/HTML — wrap it as a single paragraph block
            initialData = {
                blocks: [
                    { type: 'paragraph', data: { text: rawContent } }
                ]
            };
        }
    }

    // Tools available INSIDE a column block.
    // Deliberately kept as a separate object — do NOT reference main_tools here
    // or you will create a circular reference and crash the editor.
    const column_tools = {
        alignment: { class: AlignmentTune },
        header: { class: Header, inlineToolbar: true, config: { levels: [1, 2, 3, 4], defaultLevel: 2 }, tunes: ['alignment'] },
        list: { class: EditorjsList, inlineToolbar: true },
        paragraph: { class: Paragraph, inlineToolbar: true, tunes: ['alignment'] },
        quote: { class: Quote, inlineToolbar: true },
        underline: Underline,
        image: { class: ImageTool },
        gallery: { class: GalleryTool },
        download: { class: DownloadTool },
        sectionStart: { class: SectionStartTool },
        sectionEnd: { class: SectionEndTool },
        map: { class: MapTool },
        facebook: { class: FacebookTool },
        tile: { class: TileTool },
        pricing: { class: PricingCardTool },
        video: VideoEmbedTool,
        button: ButtonTool,
        form: FormTool,
        author: AuthorTool,
        code: CodeTool,
        emoji: { class: EmojiInlineTool },
    };

    const editor = new EditorJS({
        holder: 'editorjs',
        data: initialData,
        tools: {
            alignment: { class: AlignmentTune },
            header: { class: Header, inlineToolbar: true, config: { levels: [1, 2, 3, 4], defaultLevel: 2 }, tunes: ['alignment'] },
            list: { class: EditorjsList, inlineToolbar: true },
            paragraph: { class: Paragraph, inlineToolbar: true, tunes: ['alignment'] },
            quote: { class: Quote, inlineToolbar: true },
            underline: Underline,
            image: { class: ImageTool },
            gallery: { class: GalleryTool },
            download: { class: DownloadTool },
            sectionStart: { class: SectionStartTool },
            sectionEnd: { class: SectionEndTool },
            map: { class: MapTool },
            facebook: { class: FacebookTool },
            tile: { class: TileTool },
            pricing: { class: PricingCardTool },
            columns: {
                class: editorjsColumns,
                config: {
                    EditorJsLibrary: EditorJS,
                    tools: column_tools
                }
            },
            video: VideoEmbedTool,
            button: ButtonTool,
            form: FormTool,
            author: AuthorTool,
            code: CodeTool,
            emoji: { class: EmojiInlineTool }
        },
        placeholder: 'Start writing your page content here...'
    });

    // ── Social share (OG) image upload ──
    // Reuses the same upload.php endpoint as the Editor.js Image tool.
    // On file select, POST the file, store the returned URL in the hidden
    // og_image field, and show a preview.
    //
    // Reusable image uploader: wires a file input to upload.php, stores the
    // returned URL in a hidden field, shows a preview, and supports removal.
    // Used for both the Social Share image and the Hero image.
    function wireImageUploader(fileId, hiddenId, previewId, clearId, previewAlt) {
        const fileInput = document.getElementById(fileId);
        const hidden    = document.getElementById(hiddenId);
        const preview   = document.getElementById(previewId);
        const clearBtn  = document.getElementById(clearId);

        if (!fileInput) return;

        fileInput.addEventListener('change', function () {
            const file = fileInput.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file); // upload.php expects field name "image"

            fileInput.disabled = true;

            fetch('upload.php', { method: 'POST', body: formData })
                .then((r) => r.json())
                .then((data) => {
                    if (data && data.success === 1 && data.file && data.file.url) {
                        hidden.value = data.file.url;
                        preview.innerHTML = '<img src="' + data.file.url + '" alt="' + previewAlt + '">';
                        clearBtn.style.display = '';
                    } else {
                        alert('Image upload failed. Please try a different file.');
                    }
                })
                .catch(() => alert('Image upload failed. Please try again.'))
                .finally(() => {
                    fileInput.disabled = false;
                    fileInput.value = ''; // reset so the same file can be re-picked
                });
        });

        clearBtn.addEventListener('click', function () {
            hidden.value = '';
            preview.innerHTML = '';
            clearBtn.style.display = 'none';
        });
    }

    wireImageUploader('og_image_file', 'og_image', 'og_image_preview', 'og_image_clear', 'Social share preview');
    wireImageUploader('hero_image_file', 'hero_image', 'hero_image_preview', 'hero_image_clear', 'Hero image preview');

    // Serialize Editor.js blocks into the hidden 'content' field before the
    // form submits. editor.save() is async, so we intercept submit, stop it,
    // wait for the save, then submit the form programmatically.
    document.querySelector('form').addEventListener('submit', function (event) {
        event.preventDefault();
        const form = event.target;

        editor.save().then((outputData) => {
            document.getElementById('content').value = JSON.stringify(outputData);
            form.submit();
        }).catch((error) => {
            alert('Could not save editor content — please try again.');
            console.error('Editor.js save failed:', error);
        });
    });
</script>

<?php require __DIR__ . '/footer.php'; ?>

</body>
</html>