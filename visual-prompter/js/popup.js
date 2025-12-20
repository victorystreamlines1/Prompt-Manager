/**
 * Popup Editor System for Visual Prompter
 * Handles all node editing through popup interfaces
 * Features: Draggable, Resizable, Persistent
 */

(function(global) {
    'use strict';

    const PopupEditor = {
        currentNode: null,
        isOpen: false,
        overlay: null,
        container: null,
        
        // Drag state
        isDragging: false,
        dragOffset: { x: 0, y: 0 },
        
        // Resize state
        isResizing: false,
        resizeHandle: null,
        initialSize: { width: 0, height: 0 },
        initialPos: { x: 0, y: 0 },
        initialMouse: { x: 0, y: 0 },

        /**
         * Initialize the popup system
         */
        init: function() {
            this.overlay = document.getElementById('popup-overlay');
            this.container = document.getElementById('popup-container');
            
            // Add resize handles
            this.addResizeHandles();
            
            // Initialize drag and resize
            this.initDragAndResize();
            
            // Center popup initially
            this.centerPopup();

            // Close on ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
        },
        
        /**
         * Add resize handles to the popup
         */
        addResizeHandles: function() {
            const handles = ['top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right'];
            handles.forEach(handle => {
                const el = document.createElement('div');
                el.className = `popup-resize-handle ${handle}`;
                el.dataset.handle = handle;
                this.container.appendChild(el);
            });
        },
        
        /**
         * Initialize drag and resize functionality
         */
        initDragAndResize: function() {
            const self = this;
            
            // Drag from header
            this.container.addEventListener('mousedown', (e) => {
                const header = e.target.closest('.popup-header');
                const resizeHandle = e.target.closest('.popup-resize-handle');
                
                if (resizeHandle) {
                    self.startResize(e, resizeHandle.dataset.handle);
                } else if (header && !e.target.closest('.popup-close')) {
                    self.startDrag(e);
                }
            });
            
            // Global mouse move and up
            document.addEventListener('mousemove', (e) => {
                if (self.isDragging) {
                    self.doDrag(e);
                } else if (self.isResizing) {
                    self.doResize(e);
                }
            });
            
            document.addEventListener('mouseup', () => {
                self.stopDrag();
                self.stopResize();
            });
        },
        
        /**
         * Center the popup
         */
        centerPopup: function() {
            const rect = this.container.getBoundingClientRect();
            const x = (window.innerWidth - rect.width) / 2;
            const y = (window.innerHeight - rect.height) / 2;
            this.container.style.left = x + 'px';
            this.container.style.top = Math.max(20, y) + 'px';
        },
        
        /**
         * Start dragging
         */
        startDrag: function(e) {
            this.isDragging = true;
            const rect = this.container.getBoundingClientRect();
            this.dragOffset.x = e.clientX - rect.left;
            this.dragOffset.y = e.clientY - rect.top;
            this.container.classList.add('dragging');
            this.container.querySelector('.popup-header').classList.add('dragging');
        },
        
        /**
         * Perform drag
         */
        doDrag: function(e) {
            if (!this.isDragging) return;
            
            let x = e.clientX - this.dragOffset.x;
            let y = e.clientY - this.dragOffset.y;
            
            // Keep within viewport
            const rect = this.container.getBoundingClientRect();
            x = Math.max(0, Math.min(x, window.innerWidth - rect.width));
            y = Math.max(0, Math.min(y, window.innerHeight - rect.height));
            
            this.container.style.left = x + 'px';
            this.container.style.top = y + 'px';
        },
        
        /**
         * Stop dragging
         */
        stopDrag: function() {
            if (this.isDragging) {
                this.isDragging = false;
                this.container.classList.remove('dragging');
                const header = this.container.querySelector('.popup-header');
                if (header) header.classList.remove('dragging');
            }
        },
        
        /**
         * Start resizing
         */
        startResize: function(e, handle) {
            this.isResizing = true;
            this.resizeHandle = handle;
            const rect = this.container.getBoundingClientRect();
            this.initialSize = { width: rect.width, height: rect.height };
            this.initialPos = { x: rect.left, y: rect.top };
            this.initialMouse = { x: e.clientX, y: e.clientY };
            this.container.classList.add('dragging');
            e.preventDefault();
        },
        
        /**
         * Perform resize
         */
        doResize: function(e) {
            if (!this.isResizing) return;
            
            const dx = e.clientX - this.initialMouse.x;
            const dy = e.clientY - this.initialMouse.y;
            const handle = this.resizeHandle;
            
            let newWidth = this.initialSize.width;
            let newHeight = this.initialSize.height;
            let newX = this.initialPos.x;
            let newY = this.initialPos.y;
            
            // Handle resize based on handle position
            if (handle.includes('right')) {
                newWidth = Math.max(400, this.initialSize.width + dx);
            }
            if (handle.includes('left')) {
                const w = Math.max(400, this.initialSize.width - dx);
                if (w !== this.initialSize.width - dx + 400) {
                    newX = this.initialPos.x + (this.initialSize.width - w);
                }
                newWidth = w;
            }
            if (handle.includes('bottom')) {
                newHeight = Math.max(300, this.initialSize.height + dy);
            }
            if (handle.includes('top')) {
                const h = Math.max(300, this.initialSize.height - dy);
                if (h !== this.initialSize.height - dy + 300) {
                    newY = this.initialPos.y + (this.initialSize.height - h);
                }
                newHeight = h;
            }
            
            this.container.style.width = newWidth + 'px';
            this.container.style.height = newHeight + 'px';
            this.container.style.left = newX + 'px';
            this.container.style.top = newY + 'px';
        },
        
        /**
         * Stop resizing
         */
        stopResize: function() {
            if (this.isResizing) {
                this.isResizing = false;
                this.resizeHandle = null;
                this.container.classList.remove('dragging');
            }
        },

        /**
         * Open popup for a node
         * @param {Object} node - LiteGraph node
         */
        open: function(node) {
            if (!node || !node.getPopupData) return;

            // Close any existing popup (but save first)
            if (this.isOpen && this.currentNode !== node) {
                this.save();
            }

            this.currentNode = node;
            const popupData = node.getPopupData();
            
            // Build popup HTML (preserve resize handles)
            const resizeHandles = this.container.querySelectorAll('.popup-resize-handle');
            this.container.innerHTML = this.buildPopupHTML(popupData, node.properties);
            
            // Re-add resize handles if they were removed
            if (resizeHandles.length === 0) {
                this.addResizeHandles();
            }
            
            // Show popup with animation
            this.overlay.classList.add('active');
            this.isOpen = true;

            // Center popup if not already positioned
            if (!this.container.style.left) {
                this.centerPopup();
            }

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
                    const isDescription = field.key === 'description';
                    const descControls = isDescription ? `
                        <div class="description-controls">
                            <button type="button" class="desc-btn" data-action="expand" title="Expand textarea">
                                <span>↕️</span> Expand
                            </button>
                            <button type="button" class="desc-btn clear" data-action="clear" title="Clear all content">
                                <span>🗑️</span> Clear
                            </button>
                        </div>
                    ` : '';
                    return `
                        <div class="form-group">
                            <label class="form-label" for="${fieldId}">${field.label}</label>
                            ${descControls}
                            <textarea id="${fieldId}" 
                                      class="form-textarea ${isDescription ? 'description-textarea' : ''}" 
                                      data-key="${field.key}"
                                      placeholder="${field.placeholder || ''}"
                                      style="${isDescription ? 'min-height: 150px; resize: vertical;' : ''}">${this.escapeHTML(value || '')}</textarea>
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

                case 'hostinger-db-selector':
                    return this.buildHostingerDbSelectorHTML(field);

                default:
                    return '';
            }
        },

        /**
         * Build Hostinger database selector HTML
         */
        buildHostingerDbSelectorHTML: function(field) {
            return `
                <div class="popup-section hostinger-db-section">
                    <div class="popup-section-title">
                        <span style="color: #F97316;">🗄️</span> ${field.label}
                        <div class="db-connection-indicator" id="db-connection-indicator" title="Connection status">
                            <span class="indicator-icon">⚪</span>
                            <span class="indicator-text">Not tested</span>
                        </div>
                    </div>
                    <div class="hostinger-db-container">
                        <div class="hostinger-db-row">
                            <select id="hostinger-db-select" class="form-select hostinger-db-select" data-target="${field.targetField}">
                                <option value="">-- Select a database from Hostinger --</option>
                            </select>
                            <button type="button" id="hostinger-db-refresh" class="btn-refresh" title="Refresh list">
                                🔄
                            </button>
                        </div>
                        <button type="button" id="hostinger-db-push" class="btn-push" disabled>
                            <span>⬇️</span> Push Credentials to Description
                        </button>
                        <div id="hostinger-db-status" class="hostinger-db-status"></div>
                    </div>
                </div>
            `;
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

            // Description control buttons
            document.querySelectorAll('.desc-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.dataset.action;
                    const textarea = self.container.querySelector('.description-textarea');
                    if (!textarea) return;
                    
                    if (action === 'clear') {
                        if (confirm('Clear all description content?')) {
                            textarea.value = '';
                            if (self.currentNode) {
                                self.currentNode.properties.description = '';
                            }
                            VisualPrompter.showToast('Description cleared', 'info');
                        }
                    } else if (action === 'expand') {
                        // Toggle expanded state
                        const currentHeight = parseInt(textarea.style.height) || textarea.offsetHeight;
                        if (currentHeight < 400) {
                            textarea.style.height = '400px';
                            this.innerHTML = '<span>↕️</span> Collapse';
                        } else {
                            textarea.style.height = '150px';
                            this.innerHTML = '<span>↕️</span> Expand';
                        }
                    }
                });
            });

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

            // Initialize Hostinger database selector
            this.initHostingerDbSelector();
        },

        /**
         * Hostinger databases cache
         */
        hostingerDatabases: [],

        /**
         * Initialize Hostinger database selector
         */
        initHostingerDbSelector: function() {
            const self = this;
            const select = document.getElementById('hostinger-db-select');
            const refreshBtn = document.getElementById('hostinger-db-refresh');
            const pushBtn = document.getElementById('hostinger-db-push');
            const statusDiv = document.getElementById('hostinger-db-status');

            if (!select) return;

            // Fetch databases on init
            this.fetchHostingerDatabases();

            // Refresh button
            if (refreshBtn) {
                refreshBtn.addEventListener('click', () => {
                    this.fetchHostingerDatabases(true);
                });
            }

            // Database selection change
            select.addEventListener('change', function() {
                const selectedId = this.value;
                if (pushBtn) {
                    pushBtn.disabled = !selectedId;
                }

                if (selectedId) {
                    const db = self.hostingerDatabases.find(d => String(d.id) === String(selectedId));
                    if (db) {
                        // Auto-select the database type radio button
                        self.selectDatabaseType(db.dbType);
                        
                        // Auto-fill other fields
                        self.fillDatabaseFields(db);
                        
                        if (statusDiv) {
                            statusDiv.innerHTML = `<span style="color: #10B981;">✓ Selected: ${db.name}</span>`;
                        }
                        
                        // AUTO-TEST CONNECTION when database is selected
                        self.testDatabaseConnection(db);
                    }
                } else {
                    if (statusDiv) {
                        statusDiv.innerHTML = '';
                    }
                    // Reset connection indicator
                    self.updateConnectionIndicator('idle');
                }
            });

            // Push button
            if (pushBtn) {
                pushBtn.addEventListener('click', () => {
                    const selectedId = select.value;
                    if (selectedId) {
                        const db = self.hostingerDatabases.find(d => d.id === selectedId);
                        if (db) {
                            self.pushCredentialsToDescription(db);
                        }
                    }
                });
            }
        },

        /**
         * Fetch Hostinger databases from API
         */
        fetchHostingerDatabases: function(forceRefresh = false) {
            const self = this;
            const select = document.getElementById('hostinger-db-select');
            const statusDiv = document.getElementById('hostinger-db-status');
            const refreshBtn = document.getElementById('hostinger-db-refresh');

            if (!select) return;

            // Show loading state
            select.innerHTML = '<option value="">Loading databases...</option>';
            select.disabled = true;
            if (refreshBtn) refreshBtn.disabled = true;
            if (statusDiv) statusDiv.innerHTML = '<span style="color: #6366F1;">🔄 Fetching from Hostinger...</span>';

            // Get correct API path
            const apiUrl = this.getApiBasePath() + 'get-databases.php';
            console.log('🔗 Fetching databases from:', apiUrl);

            // Fetch from API
            fetch(apiUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.databases) {
                        self.hostingerDatabases = data.databases;
                        self.populateHostingerDbSelect(data.databases);
                        
                        if (statusDiv) {
                            statusDiv.innerHTML = `<span style="color: #10B981;">✓ ${data.count} database(s) available</span>`;
                        }
                    } else {
                        throw new Error(data.error || 'Failed to fetch databases');
                    }
                })
                .catch(error => {
                    console.error('❌ Error fetching Hostinger databases:', error);
                    select.innerHTML = '<option value="">Error loading databases</option>';
                    if (statusDiv) {
                        statusDiv.innerHTML = `<span style="color: #EF4444;">❌ ${error.message}</span>`;
                    }
                })
                .finally(() => {
                    select.disabled = false;
                    if (refreshBtn) refreshBtn.disabled = false;
                });
        },

        /**
         * Populate the Hostinger database dropdown
         */
        populateHostingerDbSelect: function(databases) {
            const select = document.getElementById('hostinger-db-select');
            if (!select) return;

            let options = '<option value="">-- Select a database from Hostinger --</option>';
            
            databases.forEach(db => {
                const typeIcon = this.getDatabaseTypeIcon(db.dbType);
                options += `<option value="${db.id}" data-type="${db.dbType}">${typeIcon} ${db.name} (${db.dbName})</option>`;
            });

            select.innerHTML = options;
        },

        /**
         * Get icon for database type
         */
        getDatabaseTypeIcon: function(dbType) {
            const icons = {
                'mysql': '🐬',
                'postgresql': '🐘',
                'mongodb': '🍃',
                'sqlite': '📦',
                'redis': '🔴',
                'other': '💾'
            };
            return icons[dbType] || icons['other'];
        },

        /**
         * Auto-select database type radio button
         */
        selectDatabaseType: function(dbType) {
            const radioInputs = document.querySelectorAll('input[data-key="dbType"]');
            radioInputs.forEach(radio => {
                if (radio.value === dbType) {
                    radio.checked = true;
                    // Update the node property immediately
                    if (this.currentNode) {
                        this.currentNode.properties.dbType = dbType;
                    }
                }
            });
        },

        /**
         * Auto-fill database fields
         */
        fillDatabaseFields: function(db) {
            // Fill host
            const hostInput = document.querySelector('[data-key="host"]');
            if (hostInput) {
                hostInput.value = db.host;
                if (this.currentNode) this.currentNode.properties.host = db.host;
            }

            // Fill port
            const portInput = document.querySelector('[data-key="port"]');
            if (portInput) {
                portInput.value = db.port;
                if (this.currentNode) this.currentNode.properties.port = db.port;
            }

            // Fill database name
            const dbNameInput = document.querySelector('[data-key="database"]');
            if (dbNameInput) {
                dbNameInput.value = db.dbName;
                if (this.currentNode) this.currentNode.properties.database = db.dbName;
            }

            // Fill username
            const usernameInput = document.querySelector('[data-key="username"]');
            if (usernameInput) {
                usernameInput.value = db.username;
                if (this.currentNode) this.currentNode.properties.username = db.username;
            }

            // Fill title if empty
            const titleInput = document.querySelector('[data-key="title"]');
            if (titleInput && !titleInput.value) {
                titleInput.value = db.name;
                if (this.currentNode) this.currentNode.properties.title = db.name;
            }
        },

        /**
         * Push credentials to description textarea (Always appends)
         */
        pushCredentialsToDescription: function(db) {
            const descTextarea = document.querySelector('[data-key="description"]');
            if (!descTextarea) return;

            // Format credentials as text
            const credentials = `Database Credentials (${db.name})
═══════════════════════════════════════
Type: ${db.dbType.toUpperCase()}
Host: ${db.host}
Port: ${db.port}
Database: ${db.dbName}
Username: ${db.username}
Password: ${db.password}
═══════════════════════════════════════
Hosting Type: ${db.type}
Created: ${new Date(db.createdAt).toLocaleDateString()}`;

            // Always APPEND to description (accumulate)
            if (descTextarea.value.trim()) {
                descTextarea.value = descTextarea.value + '\n\n' + credentials;
            } else {
                descTextarea.value = credentials;
            }

            // Update node property
            if (this.currentNode) {
                this.currentNode.properties.description = descTextarea.value;
            }

            // Show success message
            if (window.VisualPrompter) {
                VisualPrompter.showToast('Credentials appended to description', 'success');
            }
        },

        /**
         * Show add table dialog - Now uses TableBuilder
         */
        showAddTableDialog: function() {
            // Use the advanced TableBuilder if available
            if (window.TableBuilder) {
                TableBuilder.open(this.currentNode);
                return;
            }
            
            // Fallback to simple prompt if TableBuilder not loaded
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
         * Show edit table dialog - Now uses TableBuilder for editing
         */
        showEditTableDialog: function(index) {
            const table = this.currentNode.properties.tables[index];
            if (!table) return;

            // Use the advanced TableBuilder if available
            if (window.TableBuilder) {
                // Pre-populate the TableBuilder with existing table data
                TableBuilder.open(this.currentNode);
                // Set the table name
                document.getElementById('tb-table-name').value = table.name;
                TableBuilder.tableName = table.name;
                
                // Convert existing columns to TableBuilder format
                if (table.columns && table.columns.length > 0) {
                    TableBuilder.currentFields = table.columns.map(col => {
                        // Parse type and length from "VARCHAR(255)" format
                        const typeMatch = col.type.match(/^(\w+)(?:\(([^)]+)\))?$/);
                        const sqlType = typeMatch ? typeMatch[1].toUpperCase() : 'VARCHAR';
                        const length = typeMatch && typeMatch[2] ? typeMatch[2] : '';
                        
                        return {
                            type: 'text',
                            label: col.name,
                            sqlType: sqlType,
                            length: length,
                            icon: '📝',
                            fieldName: col.name,
                            notNull: false,
                            unique: false,
                            primaryKey: col.name.toLowerCase() === 'id',
                            autoIncrement: col.name.toLowerCase() === 'id',
                            defaultValue: ''
                        };
                    });
                    TableBuilder.renderFields();
                    TableBuilder.updateSQLPreview();
                }
                return;
            }

            // Fallback to simple prompt if TableBuilder not loaded
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
         * Get API base path dynamically
         * Works whether accessed from root or subdirectory
         */
        getApiBasePath: function() {
            // Method 1: Try to get from script tag src
            const scripts = document.getElementsByTagName('script');
            for (let i = 0; i < scripts.length; i++) {
                const src = scripts[i].src;
                if (src && src.includes('visual-prompter/js/popup.js')) {
                    const idx = src.indexOf('visual-prompter/js/popup.js');
                    return src.substring(0, idx) + 'visual-prompter/api/';
                }
            }
            
            // Method 2: Build from current location
            const origin = window.location.origin;
            const pathname = window.location.pathname;
            
            // If we're at /visual-prompter.php or similar
            if (pathname.includes('visual-prompter')) {
                // Get the directory containing visual-prompter.php
                const dir = pathname.substring(0, pathname.lastIndexOf('/') + 1);
                return origin + dir + 'visual-prompter/api/';
            }
            
            // Method 3: Simple fallback - assume visual-prompter folder is at root
            return origin + '/visual-prompter/api/';
        },

        /**
         * Test database connection
         * @param {Object} db - Database credentials object
         */
        testDatabaseConnection: function(db) {
            const self = this;
            
            // Update indicator to testing state
            this.updateConnectionIndicator('testing');
            
            // Prepare the request
            const requestData = {
                host: db.host,
                port: db.port,
                dbName: db.dbName,
                username: db.username,
                password: db.password,
                dbType: db.dbType || 'mysql'
            };
            
            // Get correct API path
            const apiUrl = this.getApiBasePath() + 'test-connection.php';
            console.log('🔗 Testing connection to:', apiUrl);
            
            // Make API call to test connection
            fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    self.updateConnectionIndicator('connected', data.latency);
                    console.log('✅ Connection successful:', data);
                } else {
                    self.updateConnectionIndicator('failed', null, data.error || data.message);
                    console.log('❌ Connection failed:', data);
                }
            })
            .catch(error => {
                self.updateConnectionIndicator('failed', null, error.message);
                console.error('❌ Connection test error:', error);
            });
        },

        /**
         * Update connection indicator UI
         * @param {string} status - 'idle', 'testing', 'connected', 'failed'
         * @param {number} latency - Connection latency in ms (optional)
         * @param {string} error - Error message (optional)
         */
        updateConnectionIndicator: function(status, latency, error) {
            const indicator = document.getElementById('db-connection-indicator');
            if (!indicator) return;
            
            const iconEl = indicator.querySelector('.indicator-icon');
            const textEl = indicator.querySelector('.indicator-text');
            
            // Remove all status classes
            indicator.classList.remove('idle', 'testing', 'connected', 'failed');
            indicator.classList.add(status);
            
            switch (status) {
                case 'idle':
                    iconEl.textContent = '⚪';
                    textEl.textContent = 'Not tested';
                    indicator.title = 'Select a database to test connection';
                    break;
                    
                case 'testing':
                    iconEl.innerHTML = '<span class="spinner">⟳</span>';
                    textEl.textContent = 'Testing...';
                    indicator.title = 'Testing database connection...';
                    break;
                    
                case 'connected':
                    iconEl.textContent = '✅';
                    textEl.textContent = latency ? `Connected (${latency}ms)` : 'Connected';
                    indicator.title = `Connection successful${latency ? ' - Latency: ' + latency + 'ms' : ''}`;
                    break;
                    
                case 'failed':
                    iconEl.textContent = '❌';
                    textEl.textContent = 'Failed';
                    indicator.title = error || 'Connection failed';
                    break;
            }
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

