/**
 * Popup Editor System for Visual Prompter
 * Handles all node editing through popup interfaces
 */

(function(global) {
    'use strict';

    const PopupEditor = {
        currentNode: null,
        isOpen: false,
        overlay: null,
        container: null,

        /**
         * Initialize the popup system
         */
        init: function() {
            this.overlay = document.getElementById('popup-overlay');
            this.container = document.getElementById('popup-container');
            
            // Close on overlay click
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) {
                    this.close();
                }
            });

            // Close on ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
        },

        /**
         * Open popup for a node
         * @param {Object} node - LiteGraph node
         */
        open: function(node) {
            if (!node || !node.getPopupData) return;

            // Close any existing popup
            if (this.isOpen && this.currentNode !== node) {
                this.saveAndClose();
            }

            this.currentNode = node;
            const popupData = node.getPopupData();
            
            // Build popup HTML
            this.container.innerHTML = this.buildPopupHTML(popupData, node.properties);
            
            // Show popup with animation
            this.overlay.classList.add('active');
            this.isOpen = true;

            // Initialize form interactions
            this.initFormInteractions();
            
            // Focus first input
            const firstInput = this.container.querySelector('input, select, textarea');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        },

        /**
         * Build popup HTML structure
         * @param {Object} popupData - Node's popup configuration
         * @param {Object} properties - Current node properties
         * @returns {string} HTML string
         */
        buildPopupHTML: function(popupData, properties) {
            let fieldsHTML = '';
            
            popupData.fields.forEach(field => {
                fieldsHTML += this.buildFieldHTML(field, properties[field.key]);
            });

            return `
                <div class="popup-header" style="border-top: 3px solid ${popupData.color}">
                    <div class="popup-title">
                        <span class="popup-title-icon" style="color: ${popupData.color}">${popupData.icon}</span>
                        <span>${popupData.title}</span>
                    </div>
                    <button class="popup-close" id="popup-close-btn">×</button>
                </div>
                <div class="popup-body">
                    ${fieldsHTML}
                </div>
                <div class="popup-footer">
                    <button class="popup-btn primary" id="popup-save-btn">
                        <span>💾</span> Save Changes
                    </button>
                    <button class="popup-btn danger" id="popup-delete-btn">
                        <span>🗑️</span> Delete Node
                    </button>
                </div>
            `;
        },

        /**
         * Build HTML for a single form field
         * @param {Object} field - Field configuration
         * @param {any} value - Current value
         * @returns {string} HTML string
         */
        buildFieldHTML: function(field, value) {
            const fieldId = `field-${field.key}`;
            
            switch (field.type) {
                case 'text':
                case 'password':
                    return `
                        <div class="form-group">
                            <label class="form-label" for="${fieldId}">${field.label}</label>
                            <input type="${field.type}" 
                                   id="${fieldId}" 
                                   class="form-input" 
                                   data-key="${field.key}"
                                   placeholder="${field.placeholder || ''}"
                                   value="${this.escapeHTML(value || '')}">
                        </div>
                    `;

                case 'textarea':
                    return `
                        <div class="form-group">
                            <label class="form-label" for="${fieldId}">${field.label}</label>
                            <textarea id="${fieldId}" 
                                      class="form-textarea" 
                                      data-key="${field.key}"
                                      placeholder="${field.placeholder || ''}">${this.escapeHTML(value || '')}</textarea>
                        </div>
                    `;

                case 'code':
                    return `
                        <div class="form-group">
                            <label class="form-label" for="${fieldId}">${field.label}</label>
                            <textarea id="${fieldId}" 
                                      class="form-textarea code-input" 
                                      data-key="${field.key}"
                                      data-language="${field.language || 'text'}"
                                      placeholder="${field.placeholder || ''}"
                                      style="font-family: 'JetBrains Mono', monospace; font-size: 12px;">${this.escapeHTML(value || '')}</textarea>
                        </div>
                    `;

                case 'select':
                    let options = '';
                    field.options.forEach(opt => {
                        const selected = value === opt.value ? 'selected' : '';
                        options += `<option value="${opt.value}" ${selected}>${opt.label}</option>`;
                    });
                    return `
                        <div class="form-group">
                            <label class="form-label" for="${fieldId}">${field.label}</label>
                            <select id="${fieldId}" 
                                    class="form-select" 
                                    data-key="${field.key}"
                                    ${field.onChange ? `data-onchange="${field.onChange}"` : ''}>
                                ${options}
                            </select>
                        </div>
                    `;

                case 'radio':
                    let radios = '';
                    field.options.forEach(opt => {
                        const checked = value === opt.value ? 'checked' : '';
                        radios += `
                            <label class="form-radio">
                                <input type="radio" 
                                       name="${fieldId}" 
                                       value="${opt.value}" 
                                       data-key="${field.key}"
                                       ${checked}>
                                <span class="form-radio-custom"></span>
                                <span class="form-radio-label">${opt.label}</span>
                            </label>
                        `;
                    });
                    return `
                        <div class="form-group">
                            <label class="form-label">${field.label}</label>
                            <div class="form-radio-group">${radios}</div>
                        </div>
                    `;

                case 'table-list':
                    return this.buildTableListHTML(field, value || []);

                case 'file-list':
                    return this.buildSimpleListHTML(field, value || [], 'file');

                case 'page-list':
                    return this.buildPageListHTML(field, value || []);

                case 'component-list':
                    return this.buildSimpleListHTML(field, value || [], 'component');

                case 'method-list':
                    return this.buildSimpleListHTML(field, value || [], 'method');

                case 'param-list':
                    return this.buildParamListHTML(field, value || []);

                default:
                    return '';
            }
        },

        /**
         * Build table list HTML (for database tables)
         */
        buildTableListHTML: function(field, tables) {
            let items = '';
            tables.forEach((table, index) => {
                const columns = table.columns ? table.columns.map(c => c.name).join(', ') : '';
                items += `
                    <div class="popup-list-item" data-index="${index}">
                        <div class="popup-list-info">
                            <span class="popup-list-icon">📋</span>
                            <div>
                                <div class="popup-list-name">${this.escapeHTML(table.name || 'Untitled')}</div>
                                <div class="popup-list-meta">${this.escapeHTML(columns) || 'No columns'}</div>
                            </div>
                        </div>
                        <div class="popup-list-actions">
                            <button class="popup-list-btn edit-table" data-index="${index}" title="Edit Table">✏️</button>
                            <button class="popup-list-btn delete delete-table" data-index="${index}" title="Delete Table">🗑️</button>
                        </div>
                    </div>
                `;
            });

            return `
                <div class="popup-section">
                    <div class="popup-section-title">${field.label}</div>
                    <div class="popup-list" id="table-list" data-key="${field.key}">
                        ${items || '<div class="popup-list-item" style="justify-content: center; color: var(--text-muted);">No tables yet</div>'}
                    </div>
                    <button class="btn-add" id="add-table-btn">
                        <span>+</span> Add Table
                    </button>
                </div>
            `;
        },

        /**
         * Build page list HTML (for frontend pages)
         */
        buildPageListHTML: function(field, pages) {
            let items = '';
            pages.forEach((page, index) => {
                items += `
                    <div class="popup-list-item" data-index="${index}">
                        <div class="popup-list-info">
                            <span class="popup-list-icon">🖥️</span>
                            <div>
                                <div class="popup-list-name">${this.escapeHTML(page.name || 'Untitled')}</div>
                                <div class="popup-list-meta">${this.escapeHTML(page.route || '/')}</div>
                            </div>
                        </div>
                        <div class="popup-list-actions">
                            <button class="popup-list-btn delete delete-item" data-key="${field.key}" data-index="${index}" title="Delete">🗑️</button>
                        </div>
                    </div>
                `;
            });

            return `
                <div class="popup-section">
                    <div class="popup-section-title">${field.label}</div>
                    <div class="popup-list" id="page-list" data-key="${field.key}">
                        ${items || '<div class="popup-list-item" style="justify-content: center; color: var(--text-muted);">No pages yet</div>'}
                    </div>
                    <button class="btn-add add-page-btn" data-key="${field.key}">
                        <span>+</span> Add Page
                    </button>
                </div>
            `;
        },

        /**
         * Build simple list HTML (files, components, methods)
         */
        buildSimpleListHTML: function(field, items, itemType) {
            const icons = { file: '📄', component: '🧩', method: '⚡' };
            const icon = icons[itemType] || '📎';
            
            let listItems = '';
            items.forEach((item, index) => {
                const itemName = typeof item === 'string' ? item : item.name;
                listItems += `
                    <div class="popup-list-item" data-index="${index}">
                        <div class="popup-list-info">
                            <span class="popup-list-icon">${icon}</span>
                            <div class="popup-list-name">${this.escapeHTML(itemName)}</div>
                        </div>
                        <div class="popup-list-actions">
                            <button class="popup-list-btn delete delete-item" data-key="${field.key}" data-index="${index}" title="Delete">🗑️</button>
                        </div>
                    </div>
                `;
            });

            return `
                <div class="popup-section">
                    <div class="popup-section-title">${field.label}</div>
                    <div class="popup-list" data-key="${field.key}">
                        ${listItems || `<div class="popup-list-item" style="justify-content: center; color: var(--text-muted);">No ${itemType}s yet</div>`}
                    </div>
                    <button class="btn-add add-simple-item" data-key="${field.key}" data-type="${itemType}">
                        <span>+</span> Add ${itemType.charAt(0).toUpperCase() + itemType.slice(1)}
                    </button>
                </div>
            `;
        },

        /**
         * Build parameter list HTML (for process inputs/outputs)
         */
        buildParamListHTML: function(field, params) {
            let items = '';
            params.forEach((param, index) => {
                items += `
                    <div class="popup-list-item" data-index="${index}">
                        <div class="popup-list-info">
                            <span class="popup-list-icon">${field.paramType === 'input' ? '⬅' : '➡'}</span>
                            <div>
                                <div class="popup-list-name">${this.escapeHTML(param.name || 'param')}</div>
                                <div class="popup-list-meta">${this.escapeHTML(param.type || 'any')}</div>
                            </div>
                        </div>
                        <div class="popup-list-actions">
                            <button class="popup-list-btn delete delete-item" data-key="${field.key}" data-index="${index}" title="Delete">🗑️</button>
                        </div>
                    </div>
                `;
            });

            return `
                <div class="popup-section">
                    <div class="popup-section-title">${field.label}</div>
                    <div class="popup-list" data-key="${field.key}">
                        ${items || '<div class="popup-list-item" style="justify-content: center; color: var(--text-muted);">No parameters yet</div>'}
                    </div>
                    <button class="btn-add add-param-btn" data-key="${field.key}" data-type="${field.paramType}">
                        <span>+</span> Add ${field.paramType === 'input' ? 'Input' : 'Output'}
                    </button>
                </div>
            `;
        },

        /**
         * Initialize form interactions
         */
        initFormInteractions: function() {
            const self = this;

            // Close button
            const closeBtn = document.getElementById('popup-close-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.close());
            }

            // Save button
            const saveBtn = document.getElementById('popup-save-btn');
            if (saveBtn) {
                saveBtn.addEventListener('click', () => this.saveAndClose());
            }

            // Delete button
            const deleteBtn = document.getElementById('popup-delete-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', () => this.deleteNode());
            }

            // Add table button
            const addTableBtn = document.getElementById('add-table-btn');
            if (addTableBtn) {
                addTableBtn.addEventListener('click', () => this.showAddTableDialog());
            }

            // Add page buttons
            document.querySelectorAll('.add-page-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    self.showAddPageDialog(this.dataset.key);
                });
            });

            // Add simple item buttons
            document.querySelectorAll('.add-simple-item').forEach(btn => {
                btn.addEventListener('click', function() {
                    self.showAddSimpleItemDialog(this.dataset.key, this.dataset.type);
                });
            });

            // Add parameter buttons
            document.querySelectorAll('.add-param-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    self.showAddParamDialog(this.dataset.key, this.dataset.type);
                });
            });

            // Delete item buttons
            document.querySelectorAll('.delete-item').forEach(btn => {
                btn.addEventListener('click', function() {
                    self.deleteListItem(this.dataset.key, parseInt(this.dataset.index));
                });
            });

            // Edit table buttons
            document.querySelectorAll('.edit-table').forEach(btn => {
                btn.addEventListener('click', function() {
                    self.showEditTableDialog(parseInt(this.dataset.index));
                });
            });

            // Delete table buttons
            document.querySelectorAll('.delete-table').forEach(btn => {
                btn.addEventListener('click', function() {
                    self.deleteListItem('tables', parseInt(this.dataset.index));
                });
            });
        },

        /**
         * Show add table dialog
         */
        showAddTableDialog: function() {
            const tableName = prompt('Enter table name:');
            if (tableName && tableName.trim()) {
                if (!this.currentNode.properties.tables) {
                    this.currentNode.properties.tables = [];
                }
                this.currentNode.properties.tables.push({
                    name: tableName.trim(),
                    columns: []
                });
                // Refresh popup
                this.open(this.currentNode);
                VisualPrompter.showToast('Table added', 'success');
            }
        },

        /**
         * Show edit table dialog
         */
        showEditTableDialog: function(index) {
            const table = this.currentNode.properties.tables[index];
            if (!table) return;

            const columns = table.columns || [];
            let columnsList = columns.map(c => `${c.name} (${c.type})`).join('\n');
            
            const newColumns = prompt(
                `Edit columns for "${table.name}":\nFormat: name (type)\nOne per line:`,
                columnsList
            );

            if (newColumns !== null) {
                const parsedColumns = newColumns.split('\n')
                    .filter(line => line.trim())
                    .map(line => {
                        const match = line.match(/^(.+?)\s*\((.+?)\)$/);
                        if (match) {
                            return { name: match[1].trim(), type: match[2].trim() };
                        }
                        return { name: line.trim(), type: 'VARCHAR' };
                    });
                
                this.currentNode.properties.tables[index].columns = parsedColumns;
                this.open(this.currentNode);
                VisualPrompter.showToast('Table updated', 'success');
            }
        },

        /**
         * Show add page dialog
         */
        showAddPageDialog: function(key) {
            const pageName = prompt('Enter page name:');
            if (pageName && pageName.trim()) {
                const pageRoute = prompt('Enter page route:', '/' + pageName.toLowerCase().replace(/\s+/g, '-'));
                if (!this.currentNode.properties[key]) {
                    this.currentNode.properties[key] = [];
                }
                this.currentNode.properties[key].push({
                    name: pageName.trim(),
                    route: pageRoute || '/'
                });
                this.open(this.currentNode);
                VisualPrompter.showToast('Page added', 'success');
            }
        },

        /**
         * Show add simple item dialog
         */
        showAddSimpleItemDialog: function(key, type) {
            const itemName = prompt(`Enter ${type} name:`);
            if (itemName && itemName.trim()) {
                if (!this.currentNode.properties[key]) {
                    this.currentNode.properties[key] = [];
                }
                this.currentNode.properties[key].push(itemName.trim());
                this.open(this.currentNode);
                VisualPrompter.showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} added`, 'success');
            }
        },

        /**
         * Show add parameter dialog
         */
        showAddParamDialog: function(key, type) {
            const paramName = prompt(`Enter ${type} parameter name:`);
            if (paramName && paramName.trim()) {
                const paramType = prompt('Enter parameter type:', 'string');
                if (!this.currentNode.properties[key]) {
                    this.currentNode.properties[key] = [];
                }
                this.currentNode.properties[key].push({
                    name: paramName.trim(),
                    type: paramType || 'any'
                });
                this.open(this.currentNode);
                VisualPrompter.showToast('Parameter added', 'success');
            }
        },

        /**
         * Delete list item
         */
        deleteListItem: function(key, index) {
            if (confirm('Delete this item?')) {
                if (this.currentNode.properties[key]) {
                    this.currentNode.properties[key].splice(index, 1);
                    this.open(this.currentNode);
                    VisualPrompter.showToast('Item deleted', 'info');
                }
            }
        },

        /**
         * Save current popup data to node
         */
        save: function() {
            if (!this.currentNode) return;

            // Get all form inputs
            const inputs = this.container.querySelectorAll('[data-key]');
            inputs.forEach(input => {
                const key = input.dataset.key;
                if (input.type === 'radio') {
                    if (input.checked) {
                        this.currentNode.properties[key] = input.value;
                    }
                } else {
                    this.currentNode.properties[key] = input.value;
                }
            });

            // Update node title if it has one
            if (this.currentNode.properties.title) {
                this.currentNode.title = this.currentNode.properties.title;
            }

            // Trigger canvas redraw
            if (window.VisualPrompter && window.VisualPrompter.graphCanvas) {
                window.VisualPrompter.graphCanvas.setDirty(true, true);
            }
        },

        /**
         * Save and close popup
         */
        saveAndClose: function() {
            this.save();
            this.close();
            VisualPrompter.showToast('Changes saved', 'success');
        },

        /**
         * Delete current node
         */
        deleteNode: function() {
            if (!this.currentNode) return;

            if (confirm('Delete this node? This will also remove all its connections.')) {
                const node = this.currentNode;
                this.close();
                
                if (window.VisualPrompter && window.VisualPrompter.graph) {
                    window.VisualPrompter.graph.remove(node);
                    window.VisualPrompter.updateStatusBar();
                    VisualPrompter.showToast('Node deleted', 'info');
                }
            }
        },

        /**
         * Close popup
         */
        close: function() {
            this.overlay.classList.remove('active');
            this.isOpen = false;
            this.currentNode = null;
        },

        /**
         * Escape HTML special characters
         */
        escapeHTML: function(str) {
            if (typeof str !== 'string') return '';
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    };

    // Export to global
    global.PopupEditor = PopupEditor;

})(typeof window !== 'undefined' ? window : this);

