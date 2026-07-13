<?php
// /SonaCMS-V1.1/app/authors.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/auth.php';   // enforces login, starts session, provides csrf_token
require __DIR__ . '/paths.php';
require __DIR__ . '/functions.php';

$error = null;
$message = null;

// Default empty author
$author = [
    'filename'    => '',
    'name'        => '',
    'title'       => '',
    'description' => '',
    'url'         => '',
    'pic'         => '',
];
$isEditing = false;

// Load an existing author for editing
if (isset($_GET['edit'])) {
    $existing = getAuthor($_GET['edit']);
    if ($existing) {
        $author = array_merge($author, $existing);
        $isEditing = true;
    } else {
        $error = "Author not found.";
    }
}

// Handle POST (save or delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security check failed. Please try again.";
    } elseif (($_POST['action'] ?? '') === 'delete') {
        // Delete
        $filename = $_POST['filename'] ?? '';
        if ($filename !== '' && deleteAuthor($filename)) {
            $message = "Author deleted.";
        } else {
            $error = "Could not delete that author.";
        }
    } else {
        // Save (add or update)
        $filename        = trim($_POST['filename'] ?? '');
        $originalFilename = trim($_POST['original_filename'] ?? '');

        $author['name']        = trim($_POST['name'] ?? '');
        $author['title']       = trim($_POST['title'] ?? '');
        $author['description'] = trim($_POST['description'] ?? '');
        $author['url']         = trim($_POST['url'] ?? '');
        $author['pic']         = trim($_POST['pic'] ?? '');
        $author['filename']    = $filename;

        if ($filename === '') {
            $error = "Filename is required.";
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $filename)) {
            $error = "Filename can only contain letters, numbers, hyphens and underscores.";
        } elseif ($author['name'] === '') {
            $error = "Author name is required.";
        } else {
            if (saveAuthor($filename, $author)) {
                // Handle rename (filename changed on an existing author)
                if ($originalFilename !== '' && $originalFilename !== $filename) {
                    deleteAuthor($originalFilename);
                }
                header('Location: authors.php');
                exit;
            } else {
                $error = "Could not save the author. Check folder permissions.";
            }
        }
    }
}

$authors = getAllAuthors();
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <title>Authors | CMS</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="sona-admin">
<div class="sona-wrap">

    <div class="sona-top-bar">
        <h2><?php echo $isEditing ? 'Edit Author' : 'Authors'; ?></h2>
        <div>
            <a href="admin.php">&larr; Back to pages</a>
            <a href="logout.php">Log out</a>
        </div>
    </div>

    <?php if ($error): ?>
        <p class="sona-error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if ($message): ?>
        <p class="sona-message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="original_filename" value="<?php echo htmlspecialchars($author['filename']); ?>">

        <div class="sona-form-columns">

            <div class="sona-form-col">

                <label class="sona-label" for="filename">Filename</label>
                <input class="sona-input" type="text" id="filename" name="filename" value="<?php echo htmlspecialchars($author['filename']); ?>" required>
                <p class="sona-hint">No spaces or extension — e.g. "jane-smith". The file saved on disk.</p>

                <label class="sona-label" for="name">Author Name</label>
                <input class="sona-input" type="text" id="name" name="name" value="<?php echo htmlspecialchars($author['name']); ?>" required>

                <label class="sona-label" for="title">Author Title</label>
                <input class="sona-input" type="text" id="title" name="title" value="<?php echo htmlspecialchars($author['title']); ?>">

                <label class="sona-label" for="url">Author URL</label>
                <input class="sona-input" type="text" id="url" name="url" value="<?php echo htmlspecialchars($author['url']); ?>">
                <p class="sona-hint">Optional link — e.g. a personal site or profile page.</p>

            </div>

            <div class="sona-form-col">

                <label class="sona-label" for="description">Author Description</label>
                <textarea class="sona-input sona-textarea" id="description" name="description" rows="4"><?php echo htmlspecialchars($author['description']); ?></textarea>

                <label class="sona-label" for="pic_file">Author Pic</label>
                <div class="sona-og-uploader">
                    <input class="sona-input" type="file" id="pic_file" accept="image/*">
                    <input type="hidden" id="pic" name="pic" value="<?php echo htmlspecialchars($author['pic']); ?>">
                    <div class="sona-og-preview" id="pic_preview">
                        <?php if (!empty($author['pic'])): ?>
                            <img src="<?php echo htmlspecialchars($author['pic']); ?>" alt="Author pic preview">
                        <?php endif; ?>
                    </div>
                    <button type="button" class="sona-og-clear" id="pic_clear"<?php echo empty($author['pic']) ? ' style="display:none;"' : ''; ?>>Remove image</button>
                </div>
                <p class="sona-hint">Upload a square image, 100px by 100px.</p>

            </div>

        </div>

        <button class="sona-btn sona-btn--block" type="submit"><?php echo $isEditing ? 'Update Author' : 'Add Author'; ?></button>
        <?php if ($isEditing): ?>
            <a class="sona-btn sona-btn--cancel" href="authors.php">Cancel edit</a>
        <?php endif; ?>
    </form>

    <h2 style="margin-top:40px;">Author List</h2>
    <table class="sona-table">
        <thead>
        <tr>
            <th>Pic</th>
            <th>Name</th>
            <th>Title</th>
            <th>Filename</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($authors)): ?>
            <tr><td colspan="5">No authors yet. Add one using the form above.</td></tr>
        <?php else: ?>
            <?php foreach ($authors as $a): ?>
                <tr>
                    <td>
                        <?php if (!empty($a['pic'])): ?>
                            <img src="<?php echo htmlspecialchars($a['pic']); ?>" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($a['name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($a['title'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($a['filename']); ?></td>
                    <td class="sona-actions">
                        <a class="sona-btn" href="authors.php?edit=<?php echo urlencode($a['filename']); ?>">Edit</a>
                        <form method="POST" onsubmit="return confirm('Delete this author? This cannot be undone.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="filename" value="<?php echo htmlspecialchars($a['filename']); ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <button type="submit" class="sona-btn sona-btn--danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

</div>

<script>
    // Author pic upload — reuses the same upload.php endpoint as the editor.
    (function () {
        const fileInput = document.getElementById('pic_file');
        const hidden    = document.getElementById('pic');
        const preview   = document.getElementById('pic_preview');
        const clearBtn  = document.getElementById('pic_clear');

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
                        preview.innerHTML = '<img src="' + data.file.url + '" alt="Author pic preview">';
                        clearBtn.style.display = '';
                    } else {
                        alert('Image upload failed. Please try a different file.');
                    }
                })
                .catch(() => alert('Image upload failed. Please try again.'))
                .finally(() => {
                    fileInput.disabled = false;
                    fileInput.value = '';
                });
        });

        clearBtn.addEventListener('click', function () {
            hidden.value = '';
            preview.innerHTML = '';
            clearBtn.style.display = 'none';
        });
    })();
</script>

<?php require __DIR__ . '/footer.php'; ?>

</body>
</html>