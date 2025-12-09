// ============ LOCAL STORAGE KEYS ============
const STORAGE_KEYS = {
    TEMPLATES: 'reportPrompter_templates',
    SAVED_PROMPTS: 'reportPrompter_savedPrompts',
    UPLOADED_FILES: 'reportPrompter_uploadedFiles',
    FOLDER_PATH: 'reportPrompter_folderPath',
    FILE_MODE: 'reportPrompter_fileMode'
};

// ============ STATE ============
let promptTemplates = [];
let activePrompts = new Set();
let currentPreviewTemplate = null;
let savedPromptsList = [];
let activeSavedPrompts = new Set();
let currentPreviewSaved = null;
let editorFiles = new Map();
let currentFileMode = 'content';
let selectedFolderPath = '';

let distributionState = {
    value: 1,
    enabled: false,
    startMarker: '═══ WORK DISTRIBUTION ═══',
    endMarker: '═══════════════════════════'
};

// ============ INITIALIZATION ============
document.addEventListener('DOMContentLoaded', () => {
    loadFromLocalStorage();
    renderPromptList();
    renderSavedPrompts();
    renderUploadedFiles();
    setupEventListeners();
    initDistributionSlider();
    initResizeHandle();
    initFolderPath();
});

// Load all data from localStorage
function loadFromLocalStorage() {
    // Load templates
    const templatesData = localStorage.getItem(STORAGE_KEYS.TEMPLATES);
    promptTemplates = templatesData ? JSON.parse(templatesData) : [];
    
    // Load saved prompts
    const savedData = localStorage.getItem(STORAGE_KEYS.SAVED_PROMPTS);
    savedPromptsList = savedData ? JSON.parse(savedData) : [];
    
    // Load file mode
    const fileMode = localStorage.getItem(STORAGE_KEYS.FILE_MODE);
    if (fileMode) {
        currentFileMode = fileMode;
        setFileMode(currentFileMode);
    }
}

// Initialize folder path from storage
function initFolderPath() {
    selectedFolderPath = localStorage.getItem(STORAGE_KEYS.FOLDER_PATH) || '';
    if (selectedFolderPath) {
        updateFolderUI(selectedFolderPath);
    }
}

// ============ TEMPLATE FUNCTIONS (localStorage) ============

function saveTemplatesToStorage() {
    localStorage.setItem(STORAGE_KEYS.TEMPLATES, JSON.stringify(promptTemplates));
}

function generateId() {
    return Date.now() + Math.random().toString(36).substr(2, 9);
}

// Render prompt checkboxes
function renderPromptList(searchTerm = '') {
    const container = document.getElementById('promptList');
    const noResults = document.getElementById('promptNoResults');
    const searchLower = searchTerm.toLowerCase().trim();

    const filteredTemplates = searchLower ?
        promptTemplates.filter(p =>
            p.name.toLowerCase().includes(searchLower) ||
            p.content.toLowerCase().includes(searchLower)
        ) : promptTemplates;

    if (filteredTemplates.length === 0) {
        container.innerHTML = '';
        noResults.style.display = 'block';
        return;
    }

    noResults.style.display = 'none';

    container.innerHTML = filteredTemplates.map(prompt => {
        const isChecked = activePrompts.has(prompt.id);
        const highlightedName = searchLower ? highlightText(prompt.name, searchLower) : prompt.name;
        const contentPreview = prompt.content.replace(/\n/g, ' ').substring(0, 50) + '...';

        return `
            <div class="prompt-item ${isChecked ? 'checked' : ''}" data-id="${prompt.id}">
                <div class="prompt-item-checkbox" onclick="togglePrompt('${prompt.id}')">
                    <input type="checkbox" ${isChecked ? 'checked' : ''}>
                    <div class="checkbox-box"><i class="fas fa-check"></i></div>
                </div>
                <div class="prompt-item-content" onclick="openTemplatePreview('${prompt.id}')">
                    <div class="prompt-item-name">${highlightedName}</div>
                    <div class="prompt-item-preview">${escapeHtml(contentPreview)}</div>
                </div>
                <div class="prompt-item-actions">
                    <button type="button" class="prompt-action-icon copy" onclick="copyTemplate('${prompt.id}')" title="Copy">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button type="button" class="prompt-action-icon edit" onclick="openEditTemplateModal('${prompt.id}')" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="prompt-action-icon delete" onclick="confirmDeleteTemplate('${prompt.id}')" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');

    updatePromptCounter();
}

// Open Add Template Modal
function openAddTemplateModal() {
    const modal = document.getElementById('templateModal');
    const title = document.getElementById('templateModalTitle');
    const saveText = document.getElementById('templateSaveText');
    const nameInput = document.getElementById('templateNameInput');
    const contentInput = document.getElementById('templateContentInput');
    const editId = document.getElementById('templateEditId');

    title.innerHTML = '<i class="fas fa-plus-circle"></i> <span>Add New Template</span>';
    saveText.textContent = 'Add Template';
    nameInput.value = '';
    contentInput.value = '';
    editId.value = '';

    modal.classList.add('active');
    nameInput.focus();
}

// Open Edit Template Modal
function openEditTemplateModal(id) {
    const template = promptTemplates.find(t => t.id === id);
    if (!template) return;

    const modal = document.getElementById('templateModal');
    const title = document.getElementById('templateModalTitle');
    const saveText = document.getElementById('templateSaveText');
    const nameInput = document.getElementById('templateNameInput');
    const contentInput = document.getElementById('templateContentInput');
    const editId = document.getElementById('templateEditId');

    title.innerHTML = '<i class="fas fa-edit"></i> <span>Edit Template</span>';
    saveText.textContent = 'Save Changes';
    nameInput.value = template.name;
    contentInput.value = template.content;
    editId.value = id;

    modal.classList.add('active');
    nameInput.focus();
}

// Close Template Modal
function closeTemplateModal() {
    document.getElementById('templateModal').classList.remove('active');
}

// Save Template (Add or Update) - localStorage
function saveTemplate() {
    const nameInput = document.getElementById('templateNameInput');
    const contentInput = document.getElementById('templateContentInput');
    const editId = document.getElementById('templateEditId');

    const name = nameInput.value.trim();
    const content = contentInput.value.trim();
    const id = editId.value;

    if (!name || !content) {
        showToast('Please fill in both name and content', 'error');
        return;
    }

    if (id) {
        // Update existing
        const index = promptTemplates.findIndex(t => t.id === id);
        if (index !== -1) {
            promptTemplates[index].name = name;
            promptTemplates[index].content = content;
            promptTemplates[index].updated_at = new Date().toISOString();
        }
        showToast('Template updated successfully!', 'success');
    } else {
        // Add new
        const newTemplate = {
            id: generateId(),
            name: name,
            content: content,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };
        promptTemplates.push(newTemplate);
        showToast('Template added successfully!', 'success');
    }

    saveTemplatesToStorage();
    closeTemplateModal();
    renderPromptList();
}

// Confirm Delete Template
function confirmDeleteTemplate(id) {
    const template = promptTemplates.find(t => t.id === id);
    if (!template) return;

    showConfirmModal({
        title: 'Delete Template?',
        message: `Are you sure you want to delete "${template.name}"?`,
        icon: 'fa-trash-alt',
        type: 'warning',
        confirmText: 'Delete',
        confirmIcon: 'fa-trash-alt',
        onConfirm: () => deleteTemplate(id)
    });
}

// Delete Template - localStorage
function deleteTemplate(id) {
    const index = promptTemplates.findIndex(t => t.id === id);
    if (index !== -1) {
        promptTemplates.splice(index, 1);
        saveTemplatesToStorage();
        
        if (activePrompts.has(id)) {
            activePrompts.delete(id);
            rebuildEditor();
        }
        
        showToast('Template deleted successfully!', 'success');
        renderPromptList();
    }
}

// Copy Template Content
function copyTemplate(id) {
    const template = promptTemplates.find(t => t.id === id);
    if (!template) return;

    navigator.clipboard.writeText(template.content).then(() => {
        showToast(`"${template.name}" copied to clipboard!`, 'success');
    }).catch(err => {
        showToast('Failed to copy template', 'error');
    });
}

// Open Template Preview Modal
function openTemplatePreview(id) {
    const template = promptTemplates.find(t => t.id === id);
    if (!template) return;

    currentPreviewTemplate = template;

    const modal = document.getElementById('templatePreviewModal');
    document.getElementById('previewName').textContent = template.name;
    document.getElementById('previewContent').textContent = template.content;

    document.getElementById('previewEditBtn').onclick = () => {
        closeTemplatePreview();
        openEditTemplateModal(id);
    };

    document.getElementById('previewUseBtn').onclick = () => {
        if (!activePrompts.has(id)) {
            togglePrompt(id);
        }
        closeTemplatePreview();
    };

    modal.classList.add('active');
}

// Close Template Preview
function closeTemplatePreview() {
    document.getElementById('templatePreviewModal').classList.remove('active');
    currentPreviewTemplate = null;
}

// Copy template content from preview
function copyTemplateContent() {
    if (!currentPreviewTemplate) return;

    navigator.clipboard.writeText(currentPreviewTemplate.content).then(() => {
        showToast('Content copied to clipboard!', 'success');
    }).catch(err => {
        showToast('Failed to copy content', 'error');
    });
}

// Toggle prompt in editor
function togglePrompt(id) {
    const prompt = promptTemplates.find(p => p.id === id);
    if (!prompt) return;

    const promptItem = document.querySelector(`.prompt-item[data-id="${id}"]`);
    const editor = document.getElementById('promptEditor');

    if (activePrompts.has(id)) {
        activePrompts.delete(id);
        if (promptItem) {
            promptItem.classList.remove('checked');
            const checkbox = promptItem.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = false;
        }
        rebuildEditor();
        showToast(`${prompt.name} removed`, 'info');
    } else {
        activePrompts.add(id);
        if (promptItem) {
            promptItem.classList.add('checked');
            const checkbox = promptItem.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = true;
        }

        if (editor.value.trim()) {
            editor.value += '\n\n' + prompt.content;
        } else {
            editor.value = prompt.content;
        }

        showToast(`${prompt.name} added`, 'success');
    }

    updateCounts();
    updatePromptCounter();
}

// Rebuild editor content from active prompts
function rebuildEditor() {
    const editor = document.getElementById('promptEditor');
    const contents = [];

    promptTemplates.forEach(prompt => {
        if (activePrompts.has(prompt.id)) {
            contents.push(prompt.content);
        }
    });

    savedPromptsList.forEach(prompt => {
        if (activeSavedPrompts.has(prompt.id)) {
            contents.push(prompt.content);
        }
    });

    editor.value = contents.join('\n\n');
    updateCounts();
}

// Filter prompt templates
function filterPromptTemplates() {
    const searchInput = document.getElementById('promptSearchInput');
    const clearBtn = document.getElementById('promptSearchClear');
    const searchTerm = searchInput.value;

    clearBtn.style.display = searchTerm ? 'flex' : 'none';
    renderPromptList(searchTerm);
}

// Clear prompt search
function clearPromptSearch() {
    const searchInput = document.getElementById('promptSearchInput');
    searchInput.value = '';
    document.getElementById('promptSearchClear').style.display = 'none';
    renderPromptList();
    searchInput.focus();
}

// Select all visible prompts
function selectAllPrompts() {
    const searchTerm = document.getElementById('promptSearchInput').value.toLowerCase().trim();
    const templatesToSelect = searchTerm ?
        promptTemplates.filter(p =>
            p.name.toLowerCase().includes(searchTerm) ||
            p.content.toLowerCase().includes(searchTerm)
        ) : promptTemplates;

    const editor = document.getElementById('promptEditor');
    let addedCount = 0;

    templatesToSelect.forEach(prompt => {
        if (!activePrompts.has(prompt.id)) {
            activePrompts.add(prompt.id);
            if (editor.value.trim()) {
                editor.value += '\n\n';
            }
            editor.value += prompt.content;
            addedCount++;
        }
    });

    renderPromptList(searchTerm);
    updateCounts();

    if (addedCount > 0) {
        showToast(`✅ ${addedCount} template(s) added to editor`, 'success');
    } else {
        showToast('All visible templates already selected', 'info');
    }
}

// Deselect all visible prompts
function deselectAllPrompts() {
    const searchTerm = document.getElementById('promptSearchInput').value.toLowerCase().trim();
    const templatesToDeselect = searchTerm ?
        promptTemplates.filter(p =>
            p.name.toLowerCase().includes(searchTerm) ||
            p.content.toLowerCase().includes(searchTerm)
        ) : promptTemplates;

    let removedCount = 0;

    templatesToDeselect.forEach(prompt => {
        if (activePrompts.has(prompt.id)) {
            activePrompts.delete(prompt.id);
            removedCount++;
        }
    });

    rebuildEditor();
    renderPromptList(searchTerm);
    updateCounts();

    if (removedCount > 0) {
        showToast(`🗑️ ${removedCount} template(s) removed from editor`, 'info');
    } else {
        showToast('No templates to deselect', 'info');
    }
}

// Update prompt counter
function updatePromptCounter() {
    const counter = document.getElementById('promptCounter');
    const total = promptTemplates.length;
    const selected = activePrompts.size;
    counter.textContent = `${selected}/${total}`;

    if (selected === 0) {
        counter.style.background = 'rgba(100, 100, 100, 0.15)';
        counter.style.color = 'var(--text-muted)';
    } else if (selected === total && total > 0) {
        counter.style.background = 'rgba(16, 185, 129, 0.15)';
        counter.style.color = 'var(--success)';
    } else {
        counter.style.background = 'rgba(99, 102, 241, 0.15)';
        counter.style.color = 'var(--accent-primary)';
    }
}

// ============ SAVED PROMPTS FUNCTIONS (localStorage) ============

function saveSavedPromptsToStorage() {
    localStorage.setItem(STORAGE_KEYS.SAVED_PROMPTS, JSON.stringify(savedPromptsList));
}

// Render saved prompts
function renderSavedPrompts(searchTerm = '') {
    const container = document.getElementById('savedList');
    const searchLower = searchTerm.toLowerCase().trim();

    const filteredPrompts = searchLower ?
        savedPromptsList.filter(p =>
            p.title.toLowerCase().includes(searchLower) ||
            p.content.toLowerCase().includes(searchLower)
        ) : savedPromptsList;

    if (filteredPrompts.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>${searchLower ? 'No prompts found' : 'No saved prompts yet'}</p>
            </div>
        `;
        updateSavedCounter();
        return;
    }

    container.innerHTML = filteredPrompts.map(prompt => {
        const isChecked = activeSavedPrompts.has(prompt.id);
        const highlightedTitle = searchLower ? highlightText(prompt.title, searchLower) : prompt.title;
        const contentPreview = prompt.content.replace(/\n/g, ' ').substring(0, 50) + '...';

        return `
            <div class="saved-item ${isChecked ? 'checked' : ''}" data-id="${prompt.id}">
                <div class="saved-item-checkbox" onclick="toggleSavedPrompt('${prompt.id}')">
                    <input type="checkbox" ${isChecked ? 'checked' : ''}>
                    <div class="checkbox-box"><i class="fas fa-check"></i></div>
                </div>
                <div class="saved-item-content" onclick="openSavedPreview('${prompt.id}')">
                    <div class="saved-item-name">${highlightedTitle}</div>
                    <div class="saved-item-preview">${escapeHtml(contentPreview)}</div>
                    <div class="saved-item-date">${formatDate(prompt.created_at)}</div>
                </div>
                <div class="saved-item-actions">
                    <button type="button" class="saved-action-icon copy" onclick="copySavedPrompt('${prompt.id}')" title="Copy">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button type="button" class="saved-action-icon edit" onclick="editSavedPrompt('${prompt.id}')" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="saved-action-icon delete" onclick="deletePrompt('${prompt.id}')" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');

    updateSavedCounter();
}

// Open save modal
function openSaveModal(id = null, title = '', content = null) {
    const modal = document.getElementById('saveModal');
    const titleInput = document.getElementById('promptTitle');
    const contentInput = document.getElementById('promptContent');
    const editIdInput = document.getElementById('editPromptId');

    if (id) {
        editIdInput.value = id;
        titleInput.value = title;
        contentInput.value = content;
        document.querySelector('#saveModal h3').innerHTML = '<i class="fas fa-edit"></i> Edit Prompt';
    } else {
        editIdInput.value = '';
        titleInput.value = '';
        contentInput.value = document.getElementById('promptEditor').value;
        document.querySelector('#saveModal h3').innerHTML = '<i class="fas fa-save"></i> Save Prompt';
    }

    modal.classList.add('active');
}

// Close modal
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Save prompt - localStorage
function savePrompt() {
    const title = document.getElementById('promptTitle').value.trim();
    const content = document.getElementById('promptContent').value.trim();
    const editId = document.getElementById('editPromptId').value;

    if (!title || !content) {
        showToast('Title and content required!', 'error');
        return;
    }

    if (editId) {
        // Update existing
        const index = savedPromptsList.findIndex(p => p.id === editId);
        if (index !== -1) {
            savedPromptsList[index].title = title;
            savedPromptsList[index].content = content;
            savedPromptsList[index].updated_at = new Date().toISOString();
        }
        showToast('Prompt updated successfully!', 'success');
    } else {
        // Add new
        const newPrompt = {
            id: generateId(),
            title: title,
            content: content,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };
        savedPromptsList.unshift(newPrompt);
        showToast('Prompt saved successfully!', 'success');
    }

    saveSavedPromptsToStorage();
    closeModal('saveModal');
    renderSavedPrompts();
}

// Toggle saved prompt in editor
function toggleSavedPrompt(id) {
    const prompt = savedPromptsList.find(p => p.id === id);
    if (!prompt) return;

    const savedItem = document.querySelector(`.saved-item[data-id="${id}"]`);
    const editor = document.getElementById('promptEditor');

    if (activeSavedPrompts.has(id)) {
        activeSavedPrompts.delete(id);
        if (savedItem) {
            savedItem.classList.remove('checked');
            const checkbox = savedItem.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = false;
        }
        rebuildEditor();
        showToast(`"${prompt.title}" removed`, 'info');
    } else {
        activeSavedPrompts.add(id);
        if (savedItem) {
            savedItem.classList.add('checked');
            const checkbox = savedItem.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = true;
        }

        if (editor.value.trim()) {
            editor.value += '\n\n' + prompt.content;
        } else {
            editor.value = prompt.content;
        }

        showToast(`"${prompt.title}" added`, 'success');
    }

    updateCounts();
    updateSavedCounter();
}

// Update saved prompts counter
function updateSavedCounter() {
    const counter = document.getElementById('savedCounter');
    const total = savedPromptsList.length;
    const selected = activeSavedPrompts.size;
    counter.textContent = `${selected}/${total}`;

    if (selected === 0) {
        counter.style.background = 'rgba(100, 100, 100, 0.15)';
        counter.style.color = 'var(--text-muted)';
    } else {
        counter.style.background = 'rgba(16, 185, 129, 0.15)';
        counter.style.color = 'var(--success)';
    }
}

// Select all saved prompts
function selectAllSavedPrompts() {
    const searchTerm = document.getElementById('searchPrompts').value.toLowerCase().trim();
    const promptsToSelect = searchTerm ?
        savedPromptsList.filter(p =>
            p.title.toLowerCase().includes(searchTerm) ||
            p.content.toLowerCase().includes(searchTerm)
        ) : savedPromptsList;

    const editor = document.getElementById('promptEditor');
    let addedCount = 0;

    promptsToSelect.forEach(prompt => {
        if (!activeSavedPrompts.has(prompt.id)) {
            activeSavedPrompts.add(prompt.id);
            if (editor.value.trim()) {
                editor.value += '\n\n';
            }
            editor.value += prompt.content;
            addedCount++;
        }
    });

    renderSavedPrompts(searchTerm);
    updateCounts();

    if (addedCount > 0) {
        showToast(`✅ ${addedCount} prompt(s) added to editor`, 'success');
    } else {
        showToast('All visible prompts already selected', 'info');
    }
}

// Deselect all saved prompts
function deselectAllSavedPrompts() {
    const searchTerm = document.getElementById('searchPrompts').value.toLowerCase().trim();
    const promptsToDeselect = searchTerm ?
        savedPromptsList.filter(p =>
            p.title.toLowerCase().includes(searchTerm) ||
            p.content.toLowerCase().includes(searchTerm)
        ) : savedPromptsList;

    let removedCount = 0;

    promptsToDeselect.forEach(prompt => {
        if (activeSavedPrompts.has(prompt.id)) {
            activeSavedPrompts.delete(prompt.id);
            removedCount++;
        }
    });

    rebuildEditor();
    renderSavedPrompts(searchTerm);
    updateCounts();

    if (removedCount > 0) {
        showToast(`🗑️ ${removedCount} prompt(s) removed from editor`, 'info');
    } else {
        showToast('No prompts to deselect', 'info');
    }
}

// Clear saved search
function clearSavedSearch() {
    const searchInput = document.getElementById('searchPrompts');
    searchInput.value = '';
    document.getElementById('savedSearchClear').style.display = 'none';
    renderSavedPrompts();
    searchInput.focus();
}

// Copy saved prompt content
function copySavedPrompt(id) {
    const prompt = savedPromptsList.find(p => p.id === id);
    if (!prompt) return;

    navigator.clipboard.writeText(prompt.content).then(() => {
        showToast(`"${prompt.title}" copied to clipboard!`, 'success');
    }).catch(err => {
        showToast('Failed to copy prompt', 'error');
    });
}

// Open saved prompt preview modal
function openSavedPreview(id) {
    const prompt = savedPromptsList.find(p => p.id === id);
    if (!prompt) return;

    currentPreviewSaved = prompt;

    const modal = document.getElementById('savedPreviewModal');
    document.getElementById('savedPreviewName').textContent = prompt.title;
    document.getElementById('savedPreviewDate').textContent = `Created: ${formatDate(prompt.created_at)}`;
    document.getElementById('savedPreviewContent').textContent = prompt.content;

    document.getElementById('savedPreviewEditBtn').onclick = () => {
        closeSavedPreview();
        editSavedPrompt(id);
    };

    document.getElementById('savedPreviewUseBtn').onclick = () => {
        if (!activeSavedPrompts.has(id)) {
            toggleSavedPrompt(id);
        }
        closeSavedPreview();
    };

    modal.classList.add('active');
}

// Close saved preview modal
function closeSavedPreview() {
    document.getElementById('savedPreviewModal').classList.remove('active');
    currentPreviewSaved = null;
}

// Copy saved content from preview
function copySavedContent() {
    if (!currentPreviewSaved) return;

    navigator.clipboard.writeText(currentPreviewSaved.content).then(() => {
        showToast('Content copied to clipboard!', 'success');
    }).catch(err => {
        showToast('Failed to copy content', 'error');
    });
}

// Edit saved prompt
function editSavedPrompt(id) {
    const prompt = savedPromptsList.find(p => p.id === id);
    if (!prompt) return;

    openSaveModal(id, prompt.title, prompt.content);
}

// Delete saved prompt
function deletePrompt(id) {
    const prompt = savedPromptsList.find(p => p.id === id);
    const promptTitle = prompt ? prompt.title : 'this prompt';

    showConfirmModal({
        title: 'Delete Saved Prompt?',
        message: `Are you sure you want to delete this saved prompt?`,
        icon: 'fa-trash-alt',
        type: 'warning',
        confirmText: 'Delete',
        confirmIcon: 'fa-trash-alt',
        onConfirm: () => {
            const index = savedPromptsList.findIndex(p => p.id === id);
            if (index !== -1) {
                savedPromptsList.splice(index, 1);
                saveSavedPromptsToStorage();
                
                if (activeSavedPrompts.has(id)) {
                    activeSavedPrompts.delete(id);
                    rebuildEditor();
                }
                
                showToast('Prompt deleted successfully!', 'success');
                renderSavedPrompts();
            }
        }
    });
}

// ============ FILE UPLOAD FUNCTIONS ============

// Render uploaded files (from editorFiles map)
function renderUploadedFiles() {
    const container = document.getElementById('uploadedFiles');
    const header = document.getElementById('uploadedFilesHeader');
    const countSpan = document.getElementById('filesCount');
    
    const filesArray = Array.from(editorFiles.entries());
    
    if (filesArray.length > 0) {
        header.style.display = 'flex';
        countSpan.textContent = filesArray.length;
        
        container.innerHTML = filesArray.map(([filename, data]) => `
            <div class="file-item" onclick="reAddFileToEditor('${escapeHtml(filename)}')" title="Click to add to editor">
                <i class="fas fa-file-alt"></i>
                <div class="file-info">
                    <div class="file-name">${escapeHtml(filename)}</div>
                    <div class="file-size">${data.isReference ? 'Reference' : formatFileSize(data.content.length)}</div>
                </div>
                <button class="file-delete" onclick="event.stopPropagation(); deleteFile('${escapeHtml(filename)}')" title="Delete file">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `).join('');
    } else {
        header.style.display = 'none';
        container.innerHTML = '';
    }
}

// Set file mode
function setFileMode(mode) {
    currentFileMode = mode;
    localStorage.setItem(STORAGE_KEYS.FILE_MODE, mode);
    document.getElementById('fileContentToggle').value = mode;

    const btnContent = document.getElementById('btnFullContent');
    const btnReference = document.getElementById('btnReference');

    if (mode === 'content') {
        btnContent.classList.add('active');
        btnReference.classList.remove('active');
        showToast('📄 Mode: Full Content', 'info');
    } else {
        btnContent.classList.remove('active');
        btnReference.classList.add('active');
        showToast('🔗 Mode: Reference Only', 'info');
    }
}

// Select folder path
function selectFolderPath() {
    document.getElementById('folderInput').click();
}

// Update folder UI
function updateFolderUI(folderName, fileCount = null) {
    const folderSelector = document.getElementById('folderSelector');
    const folderNameSpan = document.getElementById('folderName');
    const folderIcon = document.getElementById('folderIcon');
    const folderClearBtn = document.getElementById('folderClearBtn');
    const folderPathDisplay = document.getElementById('folderPathDisplay');
    const folderPathText = document.getElementById('folderPathText');

    selectedFolderPath = folderName;
    localStorage.setItem(STORAGE_KEYS.FOLDER_PATH, folderName);

    folderSelector.classList.add('has-folder');
    folderIcon.className = 'fas fa-folder-open';
    folderNameSpan.textContent = folderName;
    folderClearBtn.style.display = 'flex';

    folderPathDisplay.style.display = 'flex';
    folderPathText.textContent = fileCount ? `Working folder: ${folderName} (${fileCount} items)` : `Working folder: ${folderName}`;
}

// Clear folder path
function clearFolderPath() {
    const folderSelector = document.getElementById('folderSelector');
    const folderNameSpan = document.getElementById('folderName');
    const folderIcon = document.getElementById('folderIcon');
    const folderClearBtn = document.getElementById('folderClearBtn');
    const folderPathDisplay = document.getElementById('folderPathDisplay');

    selectedFolderPath = '';
    localStorage.removeItem(STORAGE_KEYS.FOLDER_PATH);

    folderSelector.classList.remove('has-folder');
    folderIcon.className = 'fas fa-folder';
    folderNameSpan.textContent = 'Set Folder Path';
    folderClearBtn.style.display = 'none';
    folderPathDisplay.style.display = 'none';

    showToast('Folder path cleared', 'info');
}

// Open file picker
function openFilePicker() {
    document.getElementById('fileInput').click();
}

// Handle file upload
async function handleFiles(files) {
    if (!files || files.length === 0) return;

    const editor = document.getElementById('promptEditor');
    const dropZone = document.getElementById('dropZone');
    let filesProcessed = 0;

    dropZone.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Processing...</span>';

    for (const file of Array.from(files)) {
        try {
            const sendFullContent = currentFileMode === 'content';
            let content = '';
            let isReference = false;

            if (sendFullContent) {
                content = await readFileAsText(file);
            } else {
                content = `[📎 File Reference: ${file.name} | Size: ${formatFileSize(file.size)} | Type: ${file.type || 'unknown'}]`;
                isReference = true;
            }

            const marker = `<!-- FILE:${file.name}:${Date.now()} -->`;

            let textToAdd = '';
            if (editor.value.trim()) {
                textToAdd = '\n\n';
            }

            if (isReference) {
                textToAdd += `${marker}\n${content}\n${marker.replace('<!--', '<!-- /END ')}`;
            } else {
                textToAdd += `${marker}\n## 📄 ${file.name}\n${content}\n${marker.replace('<!--', '<!-- /END ')}`;
            }

            editor.value += textToAdd;

            editorFiles.set(file.name, {
                marker: marker,
                content: content,
                isReference: isReference,
                addedAt: Date.now()
            });

            filesProcessed++;
        } catch (err) {
            console.error('Error reading file:', file.name, err);
            showToast(`Error reading ${file.name}`, 'error');
        }
    }

    dropZone.innerHTML = '<i class="fas fa-cloud-arrow-up"></i><span>Drop files here</span>';

    if (filesProcessed > 0) {
        updateCounts();
        renderUploadedFiles();
        const modeText = currentFileMode === 'content' ? 'with full content' : 'as references';
        showToast(`✅ ${filesProcessed} file(s) added ${modeText}!`, 'success');
    }

    document.getElementById('fileInput').value = '';
}

// Read file as text
function readFileAsText(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => resolve(e.target.result);
        reader.onerror = (e) => reject(new Error('Failed to read file'));
        reader.readAsText(file);
    });
}

// Re-add file to editor
function reAddFileToEditor(filename) {
    const fileData = editorFiles.get(filename);
    if (!fileData) return;

    const editor = document.getElementById('promptEditor');
    
    // Check if already in editor
    if (editor.value.includes(fileData.marker)) {
        showToast(`${filename} is already in editor`, 'info');
        return;
    }

    let textToAdd = '';
    if (editor.value.trim()) {
        textToAdd = '\n\n';
    }

    if (fileData.isReference) {
        textToAdd += `${fileData.marker}\n${fileData.content}\n${fileData.marker.replace('<!--', '<!-- /END ')}`;
    } else {
        textToAdd += `${fileData.marker}\n## 📄 ${filename}\n${fileData.content}\n${fileData.marker.replace('<!--', '<!-- /END ')}`;
    }

    editor.value += textToAdd;
    updateCounts();
    showToast(`✅ ${filename} added!`, 'success');
}

// Delete file
function deleteFile(filename) {
    showConfirmModal({
        title: 'Delete File?',
        message: `Remove "${filename}" from the list?`,
        icon: 'fa-file-times',
        type: 'warning',
        confirmText: 'Delete',
        confirmIcon: 'fa-trash-alt',
        onConfirm: () => {
            removeFileFromEditor(filename);
            editorFiles.delete(filename);
            renderUploadedFiles();
            showToast(`✅ ${filename} deleted!`, 'success');
        }
    });
}

// Remove file content from editor
function removeFileFromEditor(filename) {
    const editor = document.getElementById('promptEditor');
    const fileData = editorFiles.get(filename);

    if (fileData) {
        const startMarker = fileData.marker;
        const endMarker = startMarker.replace('<!--', '<!-- /END ');

        const startIdx = editor.value.indexOf(startMarker);
        if (startIdx !== -1) {
            const endIdx = editor.value.indexOf(endMarker);
            if (endIdx !== -1) {
                const before = editor.value.substring(0, startIdx);
                const after = editor.value.substring(endIdx + endMarker.length);
                editor.value = (before + after).replace(/\n{3,}/g, '\n\n').trim();
            }
        }
        updateCounts();
    }
}

// Delete all files
function deleteAllFiles() {
    const totalCount = editorFiles.size;

    if (totalCount === 0) {
        showToast('No files to delete', 'info');
        return;
    }

    showConfirmModal({
        title: 'Delete All Files?',
        message: 'This action cannot be undone.',
        details: `<span class="file-count">${totalCount}</span> file(s) will be deleted`,
        icon: 'fa-trash-alt',
        type: 'danger',
        confirmText: 'Delete All',
        confirmIcon: 'fa-trash-alt',
        onConfirm: () => {
            const filenames = Array.from(editorFiles.keys());
            for (const filename of filenames) {
                removeFileFromEditor(filename);
            }
            editorFiles.clear();
            renderUploadedFiles();
            showToast(`✅ All ${totalCount} file(s) deleted!`, 'success');
        }
    });
}

// ============ EDITOR FUNCTIONS ============

// Clear editor
function clearEditor() {
    document.getElementById('promptEditor').value = '';
    activePrompts.clear();
    activeSavedPrompts.clear();
    editorFiles.clear();

    document.querySelectorAll('.prompt-item').forEach(item => {
        item.classList.remove('checked');
        const checkbox = item.querySelector('input[type="checkbox"]');
        if (checkbox) checkbox.checked = false;
    });

    document.querySelectorAll('.saved-item').forEach(item => {
        item.classList.remove('checked');
        const checkbox = item.querySelector('input[type="checkbox"]');
        if (checkbox) checkbox.checked = false;
    });

    renderUploadedFiles();
    updateCounts();
    updatePromptCounter();
    updateSavedCounter();
    showToast('Editor cleared', 'info');
}

// Copy prompt
async function copyPrompt() {
    const editor = document.getElementById('promptEditor');
    const text = editor.value;

    if (!text.trim()) {
        showToast('Nothing to copy!', 'error');
        return;
    }

    try {
        await navigator.clipboard.writeText(text);
        showToast('Copied to clipboard!', 'success');
    } catch (err) {
        editor.select();
        document.execCommand('copy');
        showToast('Copied to clipboard!', 'success');
    }
}

// Paste from clipboard
async function pasteToEditor() {
    const editor = document.getElementById('promptEditor');

    try {
        const clipboardText = await navigator.clipboard.readText();

        if (!clipboardText) {
            showToast('Clipboard is empty!', 'error');
            return;
        }

        const start = editor.selectionStart;
        const end = editor.selectionEnd;
        const currentText = editor.value;

        if (currentText.trim() && start === end && start === currentText.length) {
            editor.value = currentText + '\n\n' + clipboardText;
        } else if (start !== end) {
            editor.value = currentText.substring(0, start) + clipboardText + currentText.substring(end);
            editor.setSelectionRange(start + clipboardText.length, start + clipboardText.length);
        } else {
            editor.value = currentText.substring(0, start) + clipboardText + currentText.substring(start);
            editor.setSelectionRange(start + clipboardText.length, start + clipboardText.length);
        }

        updateCounts();
        showToast('Pasted from clipboard!', 'success');
        editor.focus();
    } catch (err) {
        showToast('Unable to paste. Please use Ctrl+V', 'error');
    }
}

// Update character and word counts
function updateCounts() {
    const text = document.getElementById('promptEditor').value;
    document.getElementById('charCount').textContent = text.length;
    document.getElementById('wordCount').textContent = text.trim() ? text.trim().split(/\s+/).length : 0;
}

// ============ WORK DISTRIBUTION ============

function updateDistribution(value) {
    value = parseInt(value);
    distributionState.value = value;

    const valueNumber = document.getElementById('valueNumber');
    const valueLabel = document.querySelector('.value-label');
    valueNumber.textContent = value;
    valueLabel.textContent = value === 1 ? 'Part' : 'Parts';

    const fill = document.getElementById('sliderFill');
    const percentage = ((value - 1) / 9) * 100;
    fill.style.width = `${percentage}%`;

    updateActiveLabel(value);

    if (distributionState.enabled) {
        updateEditorDistribution();
    }
}

function setDistribution(value) {
    const slider = document.getElementById('distributionSlider');
    slider.value = value;
    updateDistribution(value);
}

function updateActiveLabel(value) {
    const labels = document.querySelectorAll('.slider-label');
    labels.forEach((label, index) => {
        if (index + 1 === value) {
            label.classList.add('active');
        } else {
            label.classList.remove('active');
        }
    });
}

function getDistributionMessages(value) {
    if (value === 1) {
        return {
            full: '📋 WORK DISTRIBUTION: Single Part\n\nPlease complete this entire task in ONE comprehensive response.\nProvide a complete and thorough solution without breaking it into parts.'
        };
    } else {
        let partsBreakdown = '';
        for (let i = 1; i <= value; i++) {
            partsBreakdown += `\n• Part ${i}/${value}: ${getPartDescription(i, value)}`;
        }

        return {
            full: `📋 WORK DISTRIBUTION: ${value} Parts\n\nPlease distribute and organize your work into ${value} distinct parts:${partsBreakdown}\n\n✅ After completing each part, indicate "Part X Complete" before proceeding to the next.`
        };
    }
}

function getPartDescription(part, total) {
    const percentage = Math.round((1 / total) * 100);

    if (part === 1) return `Initial setup & foundation (~${percentage}% of work)`;
    if (part === total) return `Final completion & review (~${percentage}% of work)`;
    if (part === Math.ceil(total / 2)) return `Core implementation (~${percentage}% of work)`;

    return `Section ${part} implementation (~${percentage}% of work)`;
}

function toggleDistribution() {
    const checkbox = document.getElementById('distributionEnabled');
    const section = document.querySelector('.distribution-section');

    distributionState.enabled = checkbox.checked;

    if (checkbox.checked) {
        section.classList.add('active');
        addDistributionToEditor();
        showToast(`✅ Distribution (${distributionState.value} parts) added`, 'success');
    } else {
        section.classList.remove('active');
        removeDistributionFromEditor();
        showToast('Distribution instruction removed', 'info');
    }
}

function addDistributionToEditor() {
    const editor = document.getElementById('promptEditor');
    const messages = getDistributionMessages(distributionState.value);

    if (editor.value.includes(distributionState.startMarker)) {
        updateEditorDistribution();
        return;
    }

    const distributionBlock = `${distributionState.startMarker}\n${messages.full}\n${distributionState.endMarker}`;

    if (editor.value.trim()) {
        editor.value = editor.value.trim() + '\n\n' + distributionBlock;
    } else {
        editor.value = distributionBlock;
    }

    editor.scrollTop = editor.scrollHeight;
    updateCounts();
}

function updateEditorDistribution() {
    const editor = document.getElementById('promptEditor');
    const messages = getDistributionMessages(distributionState.value);

    const startIdx = editor.value.indexOf(distributionState.startMarker);
    const endIdx = editor.value.indexOf(distributionState.endMarker);

    if (startIdx !== -1 && endIdx !== -1) {
        const before = editor.value.substring(0, startIdx);
        const after = editor.value.substring(endIdx + distributionState.endMarker.length);

        const newBlock = `${distributionState.startMarker}\n${messages.full}\n${distributionState.endMarker}`;
        editor.value = before + newBlock + after;

        updateCounts();
    } else if (distributionState.enabled) {
        addDistributionToEditor();
    }
}

function removeDistributionFromEditor() {
    const editor = document.getElementById('promptEditor');

    const startIdx = editor.value.indexOf(distributionState.startMarker);
    const endIdx = editor.value.indexOf(distributionState.endMarker);

    if (startIdx !== -1 && endIdx !== -1) {
        const before = editor.value.substring(0, startIdx);
        const after = editor.value.substring(endIdx + distributionState.endMarker.length);

        editor.value = (before + after).replace(/^\n+/, '').replace(/\n{3,}/g, '\n\n').trim();
        updateCounts();
    }
}

function initDistributionSlider() {
    updateDistribution(1);
    updateActiveLabel(1);
}

// ============ RESIZE HANDLE ============

function initResizeHandle() {
    const resizeHandle = document.getElementById('resizeHandle');
    const editor = document.getElementById('promptEditor');

    if (!resizeHandle || !editor) return;

    let isResizing = false;
    let startY = 0;
    let startHeight = 0;

    resizeHandle.addEventListener('mousedown', (e) => {
        isResizing = true;
        startY = e.clientY;
        startHeight = editor.offsetHeight;

        document.body.style.cursor = 'ns-resize';
        document.body.style.userSelect = 'none';

        e.preventDefault();
    });

    document.addEventListener('mousemove', (e) => {
        if (!isResizing) return;

        const deltaY = e.clientY - startY;
        const newHeight = Math.max(150, Math.min(startHeight + deltaY, window.innerHeight * 0.8));

        editor.style.height = newHeight + 'px';
    });

    document.addEventListener('mouseup', () => {
        if (isResizing) {
            isResizing = false;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
        }
    });

    // Touch support
    resizeHandle.addEventListener('touchstart', (e) => {
        isResizing = true;
        startY = e.touches[0].clientY;
        startHeight = editor.offsetHeight;
        e.preventDefault();
    });

    document.addEventListener('touchmove', (e) => {
        if (!isResizing) return;

        const deltaY = e.touches[0].clientY - startY;
        const newHeight = Math.max(150, Math.min(startHeight + deltaY, window.innerHeight * 0.8));

        editor.style.height = newHeight + 'px';
    });

    document.addEventListener('touchend', () => {
        isResizing = false;
    });
}

// ============ EVENT LISTENERS ============

function setupEventListeners() {
    // Editor input
    document.getElementById('promptEditor').addEventListener('input', updateCounts);

    // File upload
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const folderInput = document.getElementById('folderInput');

    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) {
            handleFiles(e.dataTransfer.files);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFiles(e.target.files);
            fileInput.value = '';
        }
    });

    folderInput.addEventListener('change', (e) => {
        const files = e.target.files;
        if (files.length > 0) {
            const folderPath = files[0].webkitRelativePath;
            const folderName = folderPath.split('/')[0];
            updateFolderUI(folderName, files.length);
            showToast(`📁 Folder "${folderName}" selected`, 'success');
            folderInput.value = '';
        }
    });

    // Search saved prompts
    document.getElementById('searchPrompts').addEventListener('input', (e) => {
        const searchTerm = e.target.value;
        const clearBtn = document.getElementById('savedSearchClear');
        if (clearBtn) {
            clearBtn.style.display = searchTerm ? 'flex' : 'none';
        }
        renderSavedPrompts(searchTerm);
    });
}

// ============ UTILITY FUNCTIONS ============

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function highlightText(text, search) {
    if (!search) return text;
    const regex = new RegExp(`(${escapeRegex(search)})`, 'gi');
    return text.replace(regex, '<span class="highlight">$1</span>');
}

function escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// ============ CONFIRM MODAL ============

let confirmCallback = null;

function showConfirmModal(options) {
    const modal = document.getElementById('confirmModal');
    const icon = document.getElementById('confirmIcon');
    const title = document.getElementById('confirmTitle');
    const message = document.getElementById('confirmMessage');
    const details = document.getElementById('confirmDetails');
    const deleteBtn = document.getElementById('confirmDeleteBtn');

    title.textContent = options.title || 'Confirm Action';
    message.textContent = options.message || 'Are you sure?';

    icon.className = 'confirm-icon';
    if (options.type === 'warning') icon.classList.add('warning');
    if (options.type === 'info') icon.classList.add('info');
    icon.innerHTML = `<i class="fas ${options.icon || 'fa-question-circle'}"></i>`;

    if (options.details) {
        details.innerHTML = options.details;
        details.classList.add('show');
    } else {
        details.classList.remove('show');
    }

    deleteBtn.innerHTML = `<i class="fas ${options.confirmIcon || 'fa-check'}"></i> ${options.confirmText || 'Confirm'}`;

    confirmCallback = options.onConfirm;

    modal.classList.add('active');

    document.addEventListener('keydown', handleConfirmEscape);
}

function closeConfirmModal(confirmed) {
    const modal = document.getElementById('confirmModal');
    modal.classList.remove('active');

    document.removeEventListener('keydown', handleConfirmEscape);

    if (confirmed && confirmCallback) {
        confirmCallback();
    }

    confirmCallback = null;
}

function handleConfirmEscape(e) {
    if (e.key === 'Escape') {
        closeConfirmModal(false);
    }
}

// ============ TOAST NOTIFICATIONS ============

function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;

    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        info: 'fa-info-circle'
    };

    toast.innerHTML = `
        <i class="fas ${icons[type]}"></i>
        <span>${message}</span>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100px)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

