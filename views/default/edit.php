<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */

/* 确保变量有默认值 */
if (!isset($maxPostLength)) $maxPostLength = (int)getSetting('max_post_length', '10000');
if (!isset($maxImageSize)) $maxImageSize = getMaxImageSize();
if (!isset($maxImageSizeMB)) $maxImageSizeMB = round($maxImageSize / 1024 / 1024, 1);
if (!isset($attachmentMaxCount)) $attachmentMaxCount = getAttachmentMaxCount();
if (!isset($attachmentMaxSize)) $attachmentMaxSize = getAttachmentMaxSize();
if (!isset($allowedAttachmentExts)) $allowedAttachmentExts = getAttachmentAllowedExts();

$GLOBALS['extraStyles'] = <<<CSS
.container { max-width: 900px; }
.wysiwyg-editor { min-height: 200px; max-height: 500px; padding: 1rem; outline: none; background: #fff; font-size: 14px; line-height: 1.6; resize: vertical; border: 1px solid #dee2e6; border-radius: 0 0.375rem 0.375rem 0; width: 100%; overflow-y: auto; }
.wysiwyg-editor:focus { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); outline: none; }
.wysiwyg-editor img { max-width: 100%; height: auto; }
.char-counter { font-size: 0.875rem; color: #6c757d; }
.char-counter.warning { color: #ffc107; }
.char-counter.danger { color: #dc3545; }
.image-upload-area { border: 2px dashed #dee2e6; border-radius: 0.375rem; padding: 1.5rem; text-align: center; cursor: pointer; transition: all 0.2s; }
.image-upload-area:hover { border-color: #0d6efd; background-color: #f8f9fa; }
.image-upload-area.dragover { border-color: #0d6efd; background-color: #e7f1ff; }
.image-preview-list { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem; }
.image-preview-item { position: relative; width: 100px; height: 100px; border: 1px solid #dee2e6; border-radius: 0.25rem; overflow: hidden; }
.image-preview-item img { width: 100%; height: 100%; object-fit: cover; }
.image-preview-item .image-actions { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.6); display: flex; justify-content: center; gap: 0.25rem; padding: 0.25rem; }
.image-preview-item .image-actions button { padding: 0.125rem 0.375rem; font-size: 0.75rem; }
.image-preview-item.inserted { border-color: #198754; }
.image-preview-item.inserted::after { content: '已插入'; position: absolute; top: 0; right: 0; background: #198754; color: #fff; font-size: 0.625rem; padding: 0.125rem 0.25rem; }
.editor-toolbar { display: flex; gap: 0.25rem; padding: 0.5rem; background: #f8f9fa; border: 1px solid #dee2e6; border-bottom: none; border-radius: 0.375rem 0.375rem 0 0; flex-wrap: wrap; align-items: center; }
.editor-toolbar .btn { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
.editor-toolbar .btn svg { width: 16px; height: 16px; vertical-align: middle; }
.editor-toolbar .btn.active { background-color: #0d6efd; color: #fff; }
.editor-toolbar .form-select { padding: 0.2rem 0.5rem; font-size: 0.8rem; width: auto; }
.hidden-content-block { background: #fff3cd; border: 1px dashed #ffc107; padding: 0.5rem 1rem; border-radius: 0.25rem; margin: 0.5rem 0; position: relative; display: block; cursor: text; }
.hidden-content-block::before { content: '🔒 隐藏内容（回复可见）'; font-size: 0.75rem; color: #856404; display: block; margin-bottom: 0.25rem; }
.hidden-content-block br { display: block; }
.wysiwyg-editor p { min-height: 1.2em; margin: 0 0 0.5em 0; }
.wysiwyg-editor > br { display: none; }
.wysiwyg-editor div:not(.hidden-content-block) { margin: 0.5em 0; }
.link-modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1050; }
.link-modal { background: #fff; border-radius: 0.5rem; padding: 1.5rem; width: 400px; max-width: 90%; }
CSS;

$jsMaxPostLength = $maxPostLength;
$jsMaxImageSize = $maxImageSize;
$jsMaxImageSizeMB = $maxImageSizeMB;
$jsPostId = $id;

$existingImages = [];
$insertedImageIds = [];
if (!empty($postImages) && is_array($postImages)) {
    foreach ($postImages as $img) {
        $existingImages[] = [
            'id' => $img['id'],
            'url' => SITE_URL . '/' . $img['filepath'],
            'thumb_url' => SITE_URL . '/' . $img['thumbpath'],
            'original_name' => $img['original_name'] ?? '',
            'is_inserted' => $img['is_inserted'] ?? 0
        ];
        if (!empty($img['is_inserted'])) {
            $insertedImageIds[] = $img['id'];
        }
    }
}
$jsExistingImages = json_encode($existingImages, JSON_UNESCAPED_UNICODE);
$jsInsertedImages = json_encode($insertedImageIds, JSON_UNESCAPED_UNICODE);

$existingAttachments = [];
if (!empty($postAttachments) && is_array($postAttachments)) {
    foreach ($postAttachments as $a) {
        $existingAttachments[] = [
            'id' => $a['id'],
            'original_name' => $a['original_name'],
            'file_size' => $a['file_size'],
            'file_ext' => $a['file_ext']
        ];
    }
}
$jsExistingAttachments = json_encode($existingAttachments, JSON_UNESCAPED_UNICODE);
$jsAttachmentMaxCount = $attachmentMaxCount;
$jsAttachmentMaxSize = $attachmentMaxSize;

$jsInitialContent = json_encode($content, JSON_UNESCAPED_UNICODE);

$GLOBALS['extraScripts'] = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    var editor = document.getElementById('editor-content');
    var hiddenInput = document.getElementById('content-hidden');
    var charCounter = document.getElementById('char-counter');
    var maxLength = {$jsMaxPostLength};
    var maxImageSize = {$jsMaxImageSize};
    var maxImageSizeMB = '{$jsMaxImageSizeMB}';
    var postId = {$jsPostId};
    var uploadedImages = [];
    var insertedImages = {$jsInsertedImages};
    var existingImages = {$jsExistingImages};
    
    var initialContent = {$jsInitialContent};
    if (initialContent) {
        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = initialContent;
        var hideBlocks = tempDiv.innerHTML.match(/\[hide\].*?\[\/hide\]/gs);
        if (hideBlocks) {
            hideBlocks.forEach(function(block) {
                var content = block.replace(/\[hide\]/, '').replace(/\[\/hide\]/, '');
                var replacement = '<div class="hidden-content-block" data-type="hide">' + content + '</div>';
                tempDiv.innerHTML = tempDiv.innerHTML.replace(block, replacement);
            });
        }
        editor.innerHTML = tempDiv.innerHTML;
    }
    
    function updateCharCount() {
        var text = editor.innerText || '';
        var len = text.length;
        if (charCounter) {
            charCounter.textContent = len + ' / ' + maxLength + ' 字';
            charCounter.className = 'char-counter';
            if (len > maxLength) {
                charCounter.classList.add('danger');
            } else if (len > maxLength * 0.9) {
                charCounter.classList.add('warning');
            }
        }
    }
    
    function ensureTrailingParagraph() {
        var lastChild = editor.lastElementChild;
        if (!lastChild || (lastChild.classList && lastChild.classList.contains('hidden-content-block'))) {
            var p = document.createElement('p');
            p.innerHTML = '<br>';
            editor.appendChild(p);
        }
    }
    
    editor.addEventListener('click', function(e) {
        if (e.target === editor) {
            var selection = window.getSelection();
            var range = document.createRange();
            
            var lastChild = editor.lastElementChild;
            if (lastChild) {
                range.selectNodeContents(lastChild);
                range.collapse(false);
            } else {
                range.setStart(editor, 0);
                range.collapse(true);
            }
            
            selection.removeAllRanges();
            selection.addRange(range);
        }
    });
    
    editor.addEventListener('input', function() {
        updateCharCount();
        ensureTrailingParagraph();
    });
    updateCharCount();
    ensureTrailingParagraph();
    
    function updateHiddenFields() {
        var imageIdsInput = document.getElementById('image_ids');
        var insertedImagesInput = document.getElementById('inserted_images');
        if (imageIdsInput) {
            imageIdsInput.value = uploadedImages.map(function(img) { return img.id; }).join(',');
        }
        if (insertedImagesInput) {
            insertedImagesInput.value = insertedImages.join(',');
        }
    }
    
    function execCommand(command, value) {
        document.execCommand(command, false, value || null);
        editor.focus();
    }
    
    document.getElementById('btn-bold').addEventListener('click', function(e) {
        e.preventDefault();
        execCommand('bold');
    });
    
    document.getElementById('btn-italic').addEventListener('click', function(e) {
        e.preventDefault();
        execCommand('italic');
    });
    
    document.getElementById('btn-strike').addEventListener('click', function(e) {
        e.preventDefault();
        execCommand('strikeThrough');
    });
    
    document.getElementById('btn-underline').addEventListener('click', function(e) {
        e.preventDefault();
        execCommand('underline');
    });
    
    document.getElementById('font-size').addEventListener('change', function(e) {
        var size = this.value;
        if (size) {
            execCommand('fontSize', size);
        }
        this.value = '';
    });
    
    document.getElementById('btn-link').addEventListener('click', function(e) {
        e.preventDefault();
        var selection = window.getSelection();
        var selectedText = selection.toString();
        showLinkModal(selectedText);
    });
    
    var savedSelection = null;
    
    function saveSelection() {
        var sel = window.getSelection();
        if (sel.rangeCount > 0) {
            savedSelection = sel.getRangeAt(0);
        }
    }
    
    function restoreSelection() {
        if (savedSelection) {
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(savedSelection);
        }
    }
    
    function showLinkModal(selectedText) {
        saveSelection();
        var overlay = document.createElement('div');
        overlay.className = 'link-modal-overlay';
        overlay.id = 'link-modal-overlay';
        overlay.innerHTML = '<div class="link-modal">' +
            '<h5 class="mb-3">插入链接</h5>' +
            '<div class="mb-3">' +
            '<label class="form-label">链接文字</label>' +
            '<input type="text" class="form-control" id="link-text" value="' + escapeHtml(selectedText) + '" placeholder="显示的文字">' +
            '</div>' +
            '<div class="mb-3">' +
            '<label class="form-label">链接地址</label>' +
            '<input type="url" class="form-control" id="link-url" placeholder="https://example.com">' +
            '</div>' +
            '<div class="mb-3 form-check">' +
            '<input type="checkbox" class="form-check-input" id="link-new-tab" checked>' +
            '<label class="form-check-label" for="link-new-tab">在新窗口打开</label>' +
            '</div>' +
            '<div class="d-flex gap-2 justify-content-end">' +
            '<button type="button" class="btn btn-secondary" id="link-cancel">取消</button>' +
            '<button type="button" class="btn btn-primary" id="link-confirm">插入</button>' +
            '</div>' +
            '</div>';
        document.body.appendChild(overlay);
        document.getElementById('link-text').focus();
        
        document.getElementById('link-cancel').addEventListener('click', function() {
            document.getElementById('link-modal-overlay').remove();
        });
        
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                overlay.remove();
            }
        });
        
        document.getElementById('link-confirm').addEventListener('click', function() {
            var text = document.getElementById('link-text').value;
            var url = document.getElementById('link-url').value;
            var newTab = document.getElementById('link-new-tab').checked;
            
            if (!url) {
                alert('请输入链接地址');
                return;
            }
            
            if (!url.match(/^https?:\/\//i)) {
                url = 'https://' + url;
            }
            
            restoreSelection();
            var target = newTab ? ' target="_blank"' : '';
            var linkHtml = '<a href="' + escapeHtml(url) + '"' + target + '>' + escapeHtml(text || url) + '</a>';
            document.execCommand('insertHTML', false, linkHtml);
            overlay.remove();
            updateCharCount();
        });
    }
    
    document.getElementById('insert-hide').addEventListener('click', function(e) {
        e.preventDefault();
        var selection = window.getSelection();
        var selectedText = selection.toString() || '隐藏内容';
        var hideBlock = '<div class="hidden-content-block" data-type="hide">' + selectedText + '</div>';
        document.execCommand('insertHTML', false, hideBlock);
        updateCharCount();
        ensureTrailingParagraph();
    });
    
    editor.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            var selection = window.getSelection();
            var range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;
            if (range) {
                var container = range.commonAncestorContainer;
                var hideBlock = container.nodeType === 3 ? container.parentNode.closest('.hidden-content-block') : container.closest('.hidden-content-block');
                if (hideBlock && editor.contains(hideBlock)) {
                    e.preventDefault();
                    var br1 = document.createElement('br');
                    range.insertNode(br1);
                    range.setStartAfter(br1);
                    range.collapse(true);
                    
                    var nextNode = br1.nextSibling;
                    var needExtraBr = !nextNode || (nextNode.nodeType === 3 && nextNode.textContent === '');
                    if (needExtraBr || br1 === hideBlock.lastChild) {
                        var br2 = document.createElement('br');
                        range.insertNode(br2);
                        range.setStartAfter(br2);
                        range.collapse(true);
                    }
                    
                    selection.removeAllRanges();
                    selection.addRange(range);
                    return false;
                }
            }
        }
        
        if (e.key === 'Backspace' || e.key === 'Delete') {
            var selection = window.getSelection();
            var range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;
            if (range) {
                var container = range.commonAncestorContainer;
                var hideBlock = container.nodeType === 3 ? container.parentNode.closest('.hidden-content-block') : container.closest('.hidden-content-block');
                
                if (hideBlock && editor.contains(hideBlock)) {
                    var isCollapsed = range.collapsed;
                    
                    if (!isCollapsed) {
                        return;
                    }
                    
                    if (e.key === 'Backspace') {
                        var atStart = range.startOffset === 0 && 
                                     (container === hideBlock || 
                                      (container.parentNode === hideBlock && hideBlock.firstChild === container));
                        if (atStart) {
                            e.preventDefault();
                            hideBlock.remove();
                            ensureTrailingParagraph();
                            return false;
                        }
                    }
                    
                    if (e.key === 'Delete') {
                        var atEnd = false;
                        if (container === hideBlock) {
                            atEnd = range.startOffset === hideBlock.childNodes.length;
                        } else if (container.nodeType === 3 && container.parentNode === hideBlock) {
                            atEnd = range.startOffset === container.length && hideBlock.lastChild === container;
                        }
                        if (atEnd) {
                            e.preventDefault();
                            hideBlock.remove();
                            ensureTrailingParagraph();
                            return false;
                        }
                    }
                }
            }
        }
        
        if (e.ctrlKey || e.metaKey) {
            switch(e.key.toLowerCase()) {
                case 'b':
                    e.preventDefault();
                    execCommand('bold');
                    break;
                case 'i':
                    e.preventDefault();
                    execCommand('italic');
                    break;
                case 'u':
                    e.preventDefault();
                    execCommand('underline');
                    break;
            }
        }
    });
    
    function handleFiles(files) {
        for (var i = 0; i < files.length; i++) {
            uploadFile(files[i]);
        }
    }
    
    function uploadFile(file) {
        if (!file.type.match(/^image\/(jpeg|png|gif|webp)$/)) {
            alert('仅支持 JPG、PNG、GIF、WEBP 格式的图片');
            return;
        }
        
        if (file.size > maxImageSize) {
            alert('图片大小超过限制（最大 ' + maxImageSizeMB + 'MB）');
            return;
        }
        
        var formData = new FormData();
        formData.append('image', file);
        formData.append('action', 'upload');
        
        fetch(SITE_URL + '/api/upload.php', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                addImagePreview(data.image, false);
                uploadedImages.push(data.image);
                updateHiddenFields();
            } else {
                alert(data.error || '上传失败');
            }
        })
        .catch(function(err) {
            alert('上传失败: ' + err.message);
        });
    }
    
    function addImagePreview(image, isExisting) {
        var previewList = document.getElementById('image-preview-list');
        if (!previewList) return;
        
        var item = document.createElement('div');
        item.className = 'image-preview-item';
        if (image.is_inserted || insertedImages.indexOf(image.id) !== -1) {
            item.classList.add('inserted');
        }
        item.dataset.id = image.id;
        item.innerHTML = '<img src="' + image.thumb_url + '" alt="">' +
            '<div class="image-actions">' +
            '<button type="button" class="btn btn-sm btn-success insert-btn">插入</button>' +
            '<button type="button" class="btn btn-sm btn-danger delete-btn">删除</button>' +
            '</div>';
        
        item.querySelector('.insert-btn').addEventListener('click', function(e) {
            e.stopPropagation();
            insertImageToEditor(image, item);
        });
        
        item.querySelector('.delete-btn').addEventListener('click', function(e) {
            e.stopPropagation();
            deleteImage(image.id, item);
        });
        
        previewList.appendChild(item);
    }
    
    function insertImageToEditor(image, item) {
        var imgHtml = '<img src="' + image.url + '" alt="图片" style="max-width:100%;height:auto;">';
        editor.focus();
        document.execCommand('insertHTML', false, imgHtml);
        
        if (!item.classList.contains('inserted')) {
            item.classList.add('inserted');
            insertedImages.push(image.id);
            updateHiddenFields();
        }
        updateCharCount();
    }
    
    function deleteImage(imageId, item) {
        if (!confirm('确定要删除这张图片吗？')) return;
        
        var formData = new FormData();
        formData.append('action', 'delete');
        formData.append('image_id', imageId);
        
        fetch(SITE_URL + '/api/upload.php', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                item.remove();
                uploadedImages = uploadedImages.filter(function(img) { return img.id !== imageId; });
                insertedImages = insertedImages.filter(function(id) { return id !== imageId; });
                updateHiddenFields();
            } else {
                alert(data.error || '删除失败');
            }
        });
    }
    
    existingImages.forEach(function(img) {
        uploadedImages.push(img);
        addImagePreview(img, true);
    });
    updateHiddenFields();
    
    var uploadArea = document.getElementById('image-upload-area');
    var fileInput = document.getElementById('image-input');
    
    if (uploadArea) {
        uploadArea.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (fileInput) fileInput.click();
        });
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
            if (e.dataTransfer && e.dataTransfer.files) {
                handleFiles(e.dataTransfer.files);
            }
        });
    }
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            handleFiles(this.files);
            this.value = '';
        });
    }
    
    var attachmentInput = document.getElementById('attachment-input');
    var attachmentList = document.getElementById('attachment-list');
    var attachmentIdsInput = document.getElementById('attachment_ids');
    var uploadedAttachments = {$jsExistingAttachments};
    var maxAttachmentCount = {$jsAttachmentMaxCount};
    var maxAttachmentSize = {$jsAttachmentMaxSize};
    
    if (attachmentInput) {
        attachmentInput.addEventListener('change', function() {
            var files = this.files;
            for (var i = 0; i < files.length; i++) {
                if (uploadedAttachments.length >= maxAttachmentCount) {
                    alert('最多只能上传 ' + maxAttachmentCount + ' 个附件');
                    break;
                }
                uploadAttachment(files[i]);
            }
            this.value = '';
        });
    }
    
    function uploadAttachment(file) {
        if (file.size > maxAttachmentSize) {
            alert('附件 "' + file.name + '" 大小超过限制');
            return;
        }
        
        var formData = new FormData();
        formData.append('attachment', file);
        formData.append('action', 'upload');
        
        fetch(SITE_URL + '/api/upload.php', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                addAttachmentItem(data.attachment);
                uploadedAttachments.push(data.attachment);
                updateAttachmentIds();
            } else {
                alert(data.error || '上传失败');
            }
        })
        .catch(function(err) {
            alert('上传失败: ' + err.message);
        });
    }
    
    function addAttachmentItem(attachment) {
        var item = document.createElement('div');
        item.className = 'attachment-item d-flex align-items-center justify-content-between p-2 bg-light rounded mb-2';
        item.dataset.id = attachment.id;
        item.innerHTML = '<div><span class="badge bg-secondary me-2">' + attachment.file_ext.toUpperCase() + '</span>' + escapeHtml(attachment.original_name) + ' <small class="text-muted ms-2">(' + formatFileSize(attachment.file_size) + ')</small></div><button type="button" class="btn btn-sm btn-outline-danger delete-attachment-btn" data-id="' + attachment.id + '">删除</button>';
        
        item.querySelector('.delete-attachment-btn').addEventListener('click', function() {
            deleteAttachment(attachment.id, item);
        });
        
        attachmentList.appendChild(item);
    }
    
    function deleteAttachment(attachmentId, item) {
        if (!confirm('确定要删除此附件吗？')) return;
        
        var formData = new FormData();
        formData.append('action', 'delete_attachment');
        formData.append('attachment_id', attachmentId);
        
        fetch(SITE_URL + '/api/upload.php', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                item.remove();
                uploadedAttachments = uploadedAttachments.filter(function(a) { return a.id !== attachmentId; });
                updateAttachmentIds();
            } else {
                alert(data.error || '删除失败');
            }
        });
    }
    
    function updateAttachmentIds() {
        attachmentIdsInput.value = uploadedAttachments.map(function(a) { return a.id; }).join(',');
    }
    
    function formatFileSize(bytes) {
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' B';
    }
    
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    document.querySelectorAll('.delete-attachment-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var attachmentId = parseInt(this.dataset.id);
            var item = this.closest('.attachment-item');
            deleteAttachment(attachmentId, item);
        });
    });
    
    var form = editor.closest('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            var text = editor.innerText || '';
            if (text.length > maxLength) {
                e.preventDefault();
                alert('内容超过最大字数限制（' + maxLength + '字）');
                return false;
            }
            
            var html = editor.innerHTML;
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            var hideBlocks = tempDiv.querySelectorAll('.hidden-content-block[data-type="hide"]');
            hideBlocks.forEach(function(block) {
                var textContent = block.innerHTML;
                var span = document.createElement('span');
                span.innerHTML = '[hide]' + textContent + '[/hide]';
                block.parentNode.replaceChild(span, block);
            });
            hiddenInput.value = tempDiv.innerHTML;
        });
    }
});
JS;
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-body">
                <h2 class="h4 mb-4">编辑帖子</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo escape($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" id="image_ids" name="image_ids" value="">
                    <input type="hidden" id="inserted_images" name="inserted_images" value="">
                    <input type="hidden" id="content-hidden" name="content" value="">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">标题</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo escape($title); ?>" placeholder="请输入帖子标题" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">分类<?php if (getSetting('require_category', '0') === '1'): ?> <span class="text-danger">*</span><?php endif; ?></label>
                        <select class="form-select" id="category_id" name="category_id" <?php if (getSetting('require_category', '0') === '1'): ?>required<?php endif; ?>>
                            <option value=""><?php echo getSetting('require_category', '0') === '1' ? '请选择分类' : '选择分类（可选）'; ?></option>
                            <?php 
                            $currentUserId = isLoggedIn() ? $_SESSION['user_id'] : null;
                            $categoryTree = getCategoryTree();
                            $currentCategoryAllowed = $categoryId ? canUserPostInCategory($categoryId, $currentUserId) : true;
                            $hasVisibleCategory = false;
                            foreach ($categoryTree as $cat): 
                            ?>
                                <?php if (!empty($cat['children'])): ?>
                                    <?php 
                                    $visibleChildren = [];
                                    foreach ($cat['children'] as $child) {
                                        if (canUserPostInCategory($child['id'], $currentUserId)) {
                                            $visibleChildren[] = $child;
                                        }
                                    }
                                    if (!empty($visibleChildren)):
                                        $hasVisibleCategory = true;
                                    ?>
                                    <optgroup label="<?php echo escape($cat['name']); ?>">
                                        <?php foreach ($visibleChildren as $child): ?>
                                            <option value="<?php echo $child['id']; ?>" <?php echo ($currentCategoryAllowed && $categoryId == $child['id']) ? 'selected' : ''; ?>>
                                                <?php echo escape($child['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (canUserPostInCategory($cat['id'], $currentUserId)): ?>
                                        <?php $hasVisibleCategory = true; ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($currentCategoryAllowed && $categoryId == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo escape($cat['name']); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$currentCategoryAllowed && $categoryId): ?>
                            <div class="alert alert-warning mt-2 mb-0">您当前没有该分类的发布权限，请重新选择分类</div>
                        <?php elseif (!$hasVisibleCategory): ?>
                            <div class="alert alert-warning mt-2 mb-0">您当前没有可发布的分类</div>
                        <?php endif; ?>
                        <small class="text-muted">带子分类的一级分类不能直接发帖，请选择子分类</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">图片上传</label>
                        <input type="file" id="image-input" accept="image/jpeg,image/png,image/gif,image/webp" multiple style="display:none;">
                        <div id="image-upload-area" class="image-upload-area">
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="text-muted mb-2" viewBox="0 0 16 16">
                                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                    <path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/>
                                </svg>
                                <p class="mb-0 text-muted">点击或拖拽图片到此处上传</p>
                                <small class="text-muted">支持 JPG、PNG、GIF、WEBP，单张最大 <?php echo $maxImageSizeMB; ?>MB</small>
                            </div>
                        </div>
                        <div id="image-preview-list" class="image-preview-list"></div>
                        <small class="text-muted">上传后点击「插入」可将图片插入到内容指定位置，未插入的图片将显示在帖子末尾</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">附件管理</label>
                        <input type="file" id="attachment-input" multiple class="form-control mb-2">
                        <div id="attachment-list" class="mt-2">
                            <?php if (!empty($postAttachments)): ?>
                                <?php foreach ($postAttachments as $attachment): ?>
                                    <div class="attachment-item d-flex align-items-center justify-content-between p-2 bg-light rounded mb-2" data-id="<?php echo $attachment['id']; ?>">
                                        <div>
                                            <span class="badge bg-secondary me-2"><?php echo strtoupper($attachment['file_ext']); ?></span>
                                            <?php echo escape($attachment['original_name']); ?>
                                            <small class="text-muted ms-2">(<?php echo formatFileSize($attachment['file_size']); ?>)</small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-attachment-btn" data-id="<?php echo $attachment['id']; ?>">删除</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" id="attachment_ids" name="attachment_ids" value="<?php echo !empty($postAttachments) ? implode(',', array_column($postAttachments, 'id')) : ''; ?>">
                        <small class="text-muted">支持 <?php echo implode(', ', $allowedAttachmentExts); ?> 格式，单个最大 <?php echo formatFileSize($attachmentMaxSize); ?>，最多 <?php echo $attachmentMaxCount; ?> 个</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">内容</label>
                            <span id="char-counter" class="char-counter">0 / <?php echo $maxPostLength; ?> 字</span>
                        </div>
                        <div class="editor-toolbar">
                            <select class="form-select form-select-sm" id="font-size" title="字号">
                                <option value="">字号</option>
                                <option value="1">小</option>
                                <option value="3">正常</option>
                                <option value="4">中大</option>
                                <option value="5">大</option>
                                <option value="6">很大</option>
                                <option value="7">最大</option>
                            </select>
                            <div class="vr mx-1"></div>
                            <button type="button" class="btn btn-outline-secondary" id="btn-bold" title="加粗 (Ctrl+B)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M4 2h4.5a3.5 3.5 0 0 1 2.852 5.53A3.5 3.5 0 0 1 9.5 14H4a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1zm1 7v3h4.5a1.5 1.5 0 0 0 0-3H5zm0-2h3.5a1.5 1.5 0 0 0 0-3H5v3z"/>
                                </svg>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btn-italic" title="斜体 (Ctrl+I)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M7.5 2h4a.5.5 0 0 1 0 1h-1.243l-2.4 10H9.5a.5.5 0 0 1 0 1h-4a.5.5 0 0 1 0-1h1.243l2.4-10H7.5a.5.5 0 0 1 0-1z"/>
                                </svg>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btn-strike" title="删除线">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M6.345 11.5c-.882 0-1.579-.542-1.579-1.25 0-.855.875-1.25 1.734-1.25h3.5c.882 0 1.579.542 1.579 1.25 0 .855-.875 1.25-1.734 1.25h-3.5zM8 4.5c-1.105 0-2 .672-2 1.5h4c0-.828-.895-1.5-2-1.5zm4.5 3.5H3.5a.5.5 0 0 0 0 1h3.155c-.425.385-.655.865-.655 1.5 0 1.381 1.343 2.5 3 2.5h2c1.657 0 3-1.119 3-2.5 0-.635-.23-1.115-.655-1.5H12.5a.5.5 0 0 0 0-1z"/>
                                </svg>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btn-underline" title="下划线 (Ctrl+U)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M5.5 2v5.5a3.5 3.5 0 0 0 7 0V2a.5.5 0 0 1 1 0v5.5a4.5 4.5 0 0 1-9 0V2a.5.5 0 0 1 1 0zM3 14.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5z"/>
                                </svg>
                            </button>
                            <div class="vr mx-1"></div>
                            <button type="button" class="btn btn-outline-secondary" id="btn-link" title="插入链接">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1.002 1.002 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4.018 4.018 0 0 1-.128-1.287z"/>
                                    <path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-.896-3.346L9.12 3.55a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 1 0-4.243-4.243L6.586 4.672z"/>
                                </svg>
                            </button>
                            <div class="vr mx-1"></div>
                            <button type="button" class="btn btn-outline-warning" id="insert-hide" title="插入隐藏内容（回复可见）">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                                </svg>
                            </button>
                        </div>
                        <div id="editor-content" class="wysiwyg-editor" contenteditable="true" placeholder="请输入内容..."></div>
                        <small class="text-muted">选中文字后点击工具栏按钮可设置格式，点击锁图标可插入隐藏内容</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">保存</button>
                        <a href="<?php echo SITE_URL; ?>/post.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
