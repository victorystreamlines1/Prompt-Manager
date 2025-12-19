/**
 * Table Builder - Advanced Table Creation Popup for Visual Prompter
 * Extracted and adapted from AppMaker
 */

(function(global) {
    'use strict';

    // ========================================
    // TABLE TEMPLATES (from AppMaker)
    // ========================================
    const TABLE_TEMPLATES = {
        'Human Resources': {
            icon: '👥',
            color: '#3b82f6',
            templates: {
                'Employees': [
                    { type: 'id', label: 'ID', sqlType: 'INT', length: '11', autoIncrement: true, primaryKey: true, notNull: true, icon: '🔑', fieldName: 'id' },
                    { type: 'text', label: 'First Name', sqlType: 'VARCHAR', length: '100', notNull: true, icon: '📝', fieldName: 'first_name' },
                    { type: 'text', label: 'Last Name', sqlType: 'VARCHAR', length: '100', notNull: true, icon: '📝', fieldName: 'last_name' },
                    { type: 'email', label: 'Email', sqlType: 'VARCHAR', length: '100', unique: true, notNull: true, icon: '📧', fieldName: 'email' },
                    { type: 'phone', label: 'Phone', sqlType: 'VARCHAR', length: '20', icon: '📞', fieldName: 'phone' },
                    { type: 'text', label: 'Position', sqlType: 'VARCHAR', length: '100', icon: '💼', fieldName: 'position' },
                    { type: 'number', label: 'Department ID', sqlType: 'INT', length: '11', icon: '🏢', fieldName: 'department_id' },
                    { type: 'date', label: 'Hire Date', sqlType: 'DATE', notNull: true, icon: '📅', fieldName: 'hire_date' },
                    { type: 'decimal', label: 'Salary', sqlType: 'DECIMAL', length: '10,2', icon: '💰', fieldName: 'salary' },
                    { type: 'status', label: 'Status', sqlType: 'VARCHAR', length: '20', defaultValue: 'active', icon: '⚡', fieldName: 'status' },
                    { type: 'created_at', label: 'Created At', sqlType: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', notNull: true, icon: '📅', fieldName: 'created_at' }
                ],
                'Departments': [
                    { type: 'id', label: 'ID', sqlType: 'INT', length: '11', autoIncrement: true, primaryKey: true, notNull: true, icon: '🔑', fieldName: 'id' },
                    { type: 'text', label: 'Department Name', sqlType: 'VARCHAR', length: '100', notNull: true, unique: true, icon: '🏢', fieldName: 'name' },
                    { type: 'text', label: 'Description', sqlType: 'TEXT', icon: '📄', fieldName: 'description' },
                    { type: 'number', label: 'Manager ID', sqlType: 'INT', length: '11', icon: '👤', fieldName: 'manager_id' },
                    { type: 'text', label: 'Location', sqlType: 'VARCHAR', length: '200', icon: '📍', fieldName: 'location' },
                    { type: 'created_at', label: 'Created At', sqlType: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', notNull: true, icon: '📅', fieldName: 'created_at' }
                ]
            }
        },
        'E-commerce': {
            icon: '🛒',
            color: '#10b981',
            templates: {
                'Products': [
                    { type: 'id', label: 'ID', sqlType: 'INT', length: '11', autoIncrement: true, primaryKey: true, notNull: true, icon: '🔑', fieldName: 'id' },
                    { type: 'text', label: 'Product Name', sqlType: 'VARCHAR', length: '255', notNull: true, icon: '📦', fieldName: 'name' },
                    { type: 'slug', label: 'Slug', sqlType: 'VARCHAR', length: '255', unique: true, notNull: true, icon: '🔗', fieldName: 'slug' },
                    { type: 'text', label: 'SKU', sqlType: 'VARCHAR', length: '100', unique: true, icon: '🏷️', fieldName: 'sku' },
                    { type: 'longtext', label: 'Description', sqlType: 'TEXT', icon: '📄', fieldName: 'description' },
                    { type: 'decimal', label: 'Price', sqlType: 'DECIMAL', length: '10,2', notNull: true, icon: '💰', fieldName: 'price' },
                    { type: 'decimal', label: 'Sale Price', sqlType: 'DECIMAL', length: '10,2', icon: '💸', fieldName: 'sale_price' },
                    { type: 'number', label: 'Stock Quantity', sqlType: 'INT', length: '11', defaultValue: '0', icon: '📊', fieldName: 'stock_quantity' },
                    { type: 'number', label: 'Category ID', sqlType: 'INT', length: '11', icon: '📂', fieldName: 'category_id' },
                    { type: 'image', label: 'Image Path', sqlType: 'VARCHAR', length: '500', icon: '🖼️', fieldName: 'image_path' },
                    { type: 'boolean', label: 'Is Featured', sqlType: 'TINYINT', length: '1', defaultValue: '0', icon: '⭐', fieldName: 'is_featured' },
                    { type: 'status', label: 'Status', sqlType: 'VARCHAR', length: '20', defaultValue: 'active', icon: '⚡', fieldName: 'status' },
                    { type: 'created_at', label: 'Created At', sqlType: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', notNull: true, icon: '📅', fieldName: 'created_at' },
                    { type: 'updated_at', label: 'Updated At', sqlType: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', icon: '🔄', fieldName: 'updated_at' }
                ],
                'Categories': [
                    { type: 'id', label: 'ID', sqlType: 'INT', length: '11', autoIncrement: true, primaryKey: true, notNull: true, icon: '🔑', fieldName: 'id' },
                    { type: 'text', label: 'Category Name', sqlType: 'VARCHAR', length: '100', notNull: true, icon: '📂', fieldName: 'name' },
                    { type: 'slug', label: 'Slug', sqlType: 'VARCHAR', length: '100', unique: true, icon: '🔗', fieldName: 'slug' },
                    { type: 'longtext', label: 'Description', sqlType: 'TEXT', icon: '📄', fieldName: 'description' },
                    { type: 'number', label: 'Parent ID', sqlType: 'INT', length: '11', icon: '🔼', fieldName: 'parent_id' },
                    { type: 'image', label: 'Image', sqlType: 'VARCHAR', length: '500', icon: '🖼️', fieldName: 'image' },
                    { type: 'status', label: 'Status', sqlType: 'VARCHAR', length: '20', defaultValue: 'active', icon: '⚡', fieldName: 'status' }
                ],
                'Orders': [
                    { type: 'id', label: 'ID', sqlType: 'INT', length: '11', autoIncrement: true, primaryKey: true, notNull: true, icon: '🔑', fieldName: 'id' },
                    { type: 'text', label: 'Order Number', sqlType: 'VARCHAR', length: '50', unique: true, notNull: true, icon: '🔢', fieldName: 'order_number' },
                    { type: 'number', label: 'Customer ID', sqlType: 'INT', length: '11', notNull: true, icon: '👤', fieldName: 'customer_id' },
                    { type: 'decimal', label: 'Total', sqlType: 'DECIMAL', length: '10,2', notNull: true, icon: '💰', fieldName: 'total' },
                    { type: 'status', label: 'Payment Status', sqlType: 'VARCHAR', length: '20', defaultValue: 'pending', icon: '💵', fieldName: 'payment_status' },
                    { type: 'status', label: 'Order Status', sqlType: 'VARCHAR', length: '20', defaultValue: 'pending', icon: '⚡', fieldName: 'order_status' },
                    { type: 'longtext', label: 'Shipping Address', sqlType: 'TEXT', icon: '📍', fieldName: 'shipping_address' },
                    { type: 'created_at', label: 'Created At', sqlType: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', notNull: true, icon: '📅', fieldName: 'created_at' }
                ]
            }
        },
        'Blog & CMS': {
            icon: '📰',
            color: '#8b5cf6',
            templates: {
                'Posts': [
                    { type: 'id', label: 'ID', sqlType: 'INT', length: '11', autoIncrement: true, primaryKey: true, notNull: true, icon: '🔑', fieldName: 'id' },
                    { type: 'text', label: 'Title', sqlType: 'VARCHAR', length: '255', notNull: true, icon: '📰', fieldName: 'title' },
                    { type: 'slug', label: 'Slug', sqlType: 'VARCHAR', length: '255', unique: true, notNull: true, icon: '🔗', fieldName: 'slug' },
                    { type: 'longtext', label: 'Content', sqlType: 'TEXT', notNull: true, icon: '📄', fieldName: 'content' },
                    { type: 'longtext', label: 'Excerpt', sqlType: 'TEXT', icon: '📝', fieldName: 'excerpt' },
                    { type: 'number', label: 'Author ID', sqlType: 'INT', length: '11', notNull: true, icon: '✍️', fieldName: 'author_id' },
                    { type: 'image', label: 'Featured Image', sqlType: 'VARCHAR', length: '500', icon: '🖼️', fieldName: 'featured_image' },
                    { type: 'number', label: 'Views Count', sqlType: 'INT', length: '11', defaultValue: '0', icon: '👁️', fieldName: 'views_count' },
                    { type: 'status', label: 'Status', sqlType: 'VARCHAR', length: '20', defaultValue: 'draft', icon: '⚡', fieldName: 'status' },
                    { type: 'datetime', label: 'Published At', sqlType: 'DATETIME', icon: '📅', fieldName: 'published_at' },
                    { type: 'created_at', label: 'Created At', sqlType: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', notNull: true, icon: '📅', fieldName: 'created_at' }
                ],
                'Comments': [
                    { type: 'id', label: 'ID', sqlType: 'INT', length: '11', autoIncrement: true, primaryKey: true, notNull: true, icon: '🔑', fieldName: 'id' },
                    { type: 'number', label: 'Post ID', sqlType: 'INT', length: '11', notNull: true, icon: '📰', fieldName: 'post_id' },
                    { type: 'text', label: 'Author Name', sqlType: 'VARCHAR', length: '100', notNull: true, icon: '📝', fieldName: 'author_name' },
                    { type: 'email', label: 'Author Email', sqlType: 'VARCHAR', length: '100', icon: '📧', fieldName: 'author_email' },
                    { type: 'longtext', label: 'Content', sqlType: 'TEXT', notNull: true, icon: '💬', fieldName: 'content' },
                    { type: 'status', label: 'Status', sqlType: 'VARCHAR', length: '20', defaultValue: 'pending', icon: '⚡', fieldName: 'status' },
                    { type: 'created_at', label: 'Created At', sqlType: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', notNull: true, icon: '📅', fieldName: 'created_at' }
                ]
            }
        },
        'Authentication': {
            icon: '🔐',
            color: '#ef4444',
            templates: {
                'Users': [
                    { type: 'id', label: 'ID', sqlType: 'INT', length: '11', autoIncrement: true, primaryKey: true, notNull: true, icon: '🔑', fieldName: 'id' },
                    { type: 'text', label: 'Username', sqlType: 'VARCHAR', length: '50', unique: true, notNull: true, icon: '👤', fieldName: 'username' },
                    { type: 'email', label: 'Email', sqlType: 'VARCHAR', length: '100', unique: true, notNull: true, icon: '📧', fieldName: 'email' },
                    { type: 'password', label: 'Password Hash', sqlType: 'VARCHAR', length: '255', notNull: true, icon: '🔐', fieldName: 'password_hash' },
                    { type: 'text', label: 'First Name', sqlType: 'VARCHAR', length: '100', icon: '📝', fieldName: 'first_name' },
                    { type: 'text', label: 'Last Name', sqlType: 'VARCHAR', length: '100', icon: '📝', fieldName: 'last_name' },
                    { type: 'image', label: 'Avatar', sqlType: 'VARCHAR', length: '500', icon: '🖼️', fieldName: 'avatar' },
                    { type: 'boolean', label: 'Is Active', sqlType: 'TINYINT', length: '1', defaultValue: '1', icon: '✅', fieldName: 'is_active' },
                    { type: 'datetime', label: 'Last Login', sqlType: 'DATETIME', icon: '🕐', fieldName: 'last_login' },
                    { type: 'created_at', label: 'Created At', sqlType: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', notNull: true, icon: '📅', fieldName: 'created_at' }
                ],
                'Roles': [
                    { type: 'id', label: 'ID', sqlType: 'INT', length: '11', autoIncrement: true, primaryKey: true, notNull: true, icon: '🔑', fieldName: 'id' },
                    { type: 'text', label: 'Role Name', sqlType: 'VARCHAR', length: '50', unique: true, notNull: true, icon: '🎭', fieldName: 'name' },
                    { type: 'text', label: 'Slug', sqlType: 'VARCHAR', length: '50', unique: true, notNull: true, icon: '🔗', fieldName: 'slug' },
                    { type: 'longtext', label: 'Description', sqlType: 'TEXT', icon: '📄', fieldName: 'description' },
                    { type: 'boolean', label: 'Is Active', sqlType: 'TINYINT', length: '1', defaultValue: '1', icon: '✅', fieldName: 'is_active' },
                    { type: 'created_at', label: 'Created At', sqlType: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', notNull: true, icon: '📅', fieldName: 'created_at' }
                ]
            }
        },
        'Inventory': {
            icon: '📦',
            color: '#f59e0b',
            templates: {
                'Inventory Items': [
                    { type: 'id', label: 'ID', sqlType: 'INT', length: '11', autoIncrement: true, primaryKey: true, notNull: true, icon: '🔑', fieldName: 'id' },
                    { type: 'text', label: 'Item Name', sqlType: 'VARCHAR', length: '255', notNull: true, icon: '📦', fieldName: 'name' },
                    { type: 'text', label: 'SKU', sqlType: 'VARCHAR', length: '100', unique: true, icon: '🏷️', fieldName: 'sku' },
                    { type: 'longtext', label: 'Description', sqlType: 'TEXT', icon: '📄', fieldName: 'description' },
                    { type: 'number', label: 'Quantity', sqlType: 'INT', length: '11', defaultValue: '0', notNull: true, icon: '🔢', fieldName: 'quantity' },
                    { type: 'number', label: 'Min Quantity', sqlType: 'INT', length: '11', defaultValue: '0', icon: '⚠️', fieldName: 'min_quantity' },
                    { type: 'decimal', label: 'Unit Price', sqlType: 'DECIMAL', length: '10,2', icon: '💰', fieldName: 'unit_price' },
                    { type: 'text', label: 'Location', sqlType: 'VARCHAR', length: '100', icon: '📍', fieldName: 'location' },
                    { type: 'status', label: 'Status', sqlType: 'VARCHAR', length: '20', defaultValue: 'in_stock', icon: '⚡', fieldName: 'status' },
                    { type: 'created_at', label: 'Created At', sqlType: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', notNull: true, icon: '📅', fieldName: 'created_at' }
                ]
            }
        },
        'Quick Start': {
            icon: '⚡',
            color: '#06b6d4',
            templates: {
                'Basic CRUD': [
                    { type: 'id', label: 'ID', sqlType: 'INT', length: '11', autoIncrement: true, primaryKey: true, notNull: true, icon: '🔑', fieldName: 'id' },
                    { type: 'text', label: 'Name', sqlType: 'VARCHAR', length: '255', notNull: true, icon: '📝', fieldName: 'name' },
                    { type: 'longtext', label: 'Description', sqlType: 'TEXT', icon: '📄', fieldName: 'description' },
                    { type: 'status', label: 'Status', sqlType: 'VARCHAR', length: '20', defaultValue: 'active', icon: '⚡', fieldName: 'status' },
                    { type: 'created_at', label: 'Created At', sqlType: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', notNull: true, icon: '📅', fieldName: 'created_at' },
                    { type: 'updated_at', label: 'Updated At', sqlType: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', icon: '🔄', fieldName: 'updated_at' }
                ],
                'Contact Form': [
                    { type: 'id', label: 'ID', sqlType: 'INT', length: '11', autoIncrement: true, primaryKey: true, notNull: true, icon: '🔑', fieldName: 'id' },
                    { type: 'text', label: 'Name', sqlType: 'VARCHAR', length: '100', notNull: true, icon: '📝', fieldName: 'name' },
                    { type: 'email', label: 'Email', sqlType: 'VARCHAR', length: '100', notNull: true, icon: '📧', fieldName: 'email' },
                    { type: 'phone', label: 'Phone', sqlType: 'VARCHAR', length: '20', icon: '📞', fieldName: 'phone' },
                    { type: 'text', label: 'Subject', sqlType: 'VARCHAR', length: '255', icon: '📋', fieldName: 'subject' },
                    { type: 'longtext', label: 'Message', sqlType: 'TEXT', notNull: true, icon: '💬', fieldName: 'message' },
                    { type: 'status', label: 'Status', sqlType: 'VARCHAR', length: '20', defaultValue: 'new', icon: '⚡', fieldName: 'status' },
                    { type: 'created_at', label: 'Created At', sqlType: 'TIMESTAMP', defaultValue: 'CURRENT_TIMESTAMP', notNull: true, icon: '📅', fieldName: 'created_at' }
                ]
            }
        }
    };

    // SQL Type options
    const SQL_TYPES = [
        { value: 'INT', label: 'INT', hasLength: true },
        { value: 'BIGINT', label: 'BIGINT', hasLength: true },
        { value: 'VARCHAR', label: 'VARCHAR', hasLength: true },
        { value: 'TEXT', label: 'TEXT', hasLength: false },
        { value: 'LONGTEXT', label: 'LONGTEXT', hasLength: false },
        { value: 'DECIMAL', label: 'DECIMAL', hasLength: true },
        { value: 'FLOAT', label: 'FLOAT', hasLength: false },
        { value: 'DOUBLE', label: 'DOUBLE', hasLength: false },
        { value: 'DATE', label: 'DATE', hasLength: false },
        { value: 'DATETIME', label: 'DATETIME', hasLength: false },
        { value: 'TIMESTAMP', label: 'TIMESTAMP', hasLength: false },
        { value: 'TIME', label: 'TIME', hasLength: false },
        { value: 'TINYINT', label: 'TINYINT (Boolean)', hasLength: true },
        { value: 'BOOLEAN', label: 'BOOLEAN', hasLength: false },
        { value: 'ENUM', label: 'ENUM', hasLength: true },
        { value: 'JSON', label: 'JSON', hasLength: false }
    ];

    const TableBuilder = {
        isOpen: false,
        currentFields: [],
        tableName: '',
        targetNode: null,
        modalElement: null,

        /**
         * Initialize the table builder
         */
        init: function() {
            this.createModal();
        },

        /**
         * Create the modal element
         */
        createModal: function() {
            // Check if modal already exists
            if (document.getElementById('table-builder-modal')) return;

            const modal = document.createElement('div');
            modal.id = 'table-builder-modal';
            modal.className = 'table-builder-overlay';
            modal.innerHTML = this.getModalHTML();
            document.body.appendChild(modal);
            this.modalElement = modal;

            // Bind events
            this.bindEvents();
        },

        /**
         * Get modal HTML
         */
        getModalHTML: function() {
            return `
                <div class="table-builder-container">
                    <div class="table-builder-header">
                        <div class="table-builder-title">
                            <span class="table-builder-icon">📋</span>
                            <span>Table Builder</span>
                        </div>
                        <button class="table-builder-close" id="tb-close-btn">×</button>
                    </div>
                    
                    <div class="table-builder-body">
                        <div class="table-builder-sidebar">
                            <div class="tb-section">
                                <div class="tb-section-title">📚 Templates</div>
                                <input type="text" id="tb-template-search" class="tb-input" placeholder="🔍 Search templates...">
                                <div id="tb-templates-list" class="tb-templates-list"></div>
                            </div>
                        </div>
                        
                        <div class="table-builder-main">
                            <div class="tb-section">
                                <div class="tb-section-title">⚙️ Table Configuration</div>
                                <div class="tb-form-row">
                                    <label>Table Name</label>
                                    <input type="text" id="tb-table-name" class="tb-input" placeholder="my_table">
                                </div>
                            </div>
                            
                            <div class="tb-section">
                                <div class="tb-section-title">📊 Columns <span id="tb-field-count">(0)</span></div>
                                <div id="tb-fields-list" class="tb-fields-list"></div>
                                <button id="tb-add-field-btn" class="tb-btn tb-btn-secondary">
                                    <span>➕</span> Add Column
                                </button>
                            </div>
                            
                            <div class="tb-section">
                                <div class="tb-section-title">👁️ SQL Preview</div>
                                <pre id="tb-sql-preview" class="tb-sql-preview">-- Select a template or add columns</pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-builder-footer">
                        <button id="tb-clear-btn" class="tb-btn tb-btn-danger">
                            <span>🗑️</span> Clear All
                        </button>
                        <div class="tb-footer-right">
                            <button id="tb-cancel-btn" class="tb-btn tb-btn-secondary">Cancel</button>
                            <button id="tb-push-btn" class="tb-btn tb-btn-primary">
                                <span>⬇️</span> Push to Description
                            </button>
                        </div>
                    </div>
                </div>
            `;
        },

        /**
         * Bind modal events
         */
        bindEvents: function() {
            const self = this;

            // Close button
            document.getElementById('tb-close-btn').addEventListener('click', () => this.close());
            document.getElementById('tb-cancel-btn').addEventListener('click', () => this.close());

            // Click outside to close
            this.modalElement.addEventListener('click', (e) => {
                if (e.target === this.modalElement) this.close();
            });

            // Template search
            document.getElementById('tb-template-search').addEventListener('input', (e) => {
                this.filterTemplates(e.target.value);
            });

            // Table name change
            document.getElementById('tb-table-name').addEventListener('input', (e) => {
                this.tableName = e.target.value;
                this.updateSQLPreview();
            });

            // Add field button
            document.getElementById('tb-add-field-btn').addEventListener('click', () => {
                this.addField();
            });

            // Clear button
            document.getElementById('tb-clear-btn').addEventListener('click', () => {
                if (confirm('Clear all columns?')) {
                    this.currentFields = [];
                    this.renderFields();
                    this.updateSQLPreview();
                }
            });

            // Push button
            document.getElementById('tb-push-btn').addEventListener('click', () => {
                this.pushToDescription();
            });

            // ESC key to close
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
        },

        /**
         * Open the table builder
         */
        open: function(node) {
            this.targetNode = node;
            this.isOpen = true;
            this.modalElement.classList.add('active');
            
            // Reset state
            this.currentFields = [];
            this.tableName = node.properties.title || 'my_table';
            document.getElementById('tb-table-name').value = this.tableName;
            
            // Render templates
            this.renderTemplates();
            this.renderFields();
            this.updateSQLPreview();

            // Focus table name
            setTimeout(() => document.getElementById('tb-table-name').focus(), 100);
        },

        /**
         * Close the table builder
         */
        close: function() {
            this.isOpen = false;
            this.modalElement.classList.remove('active');
            this.targetNode = null;
        },

        /**
         * Render templates list
         */
        renderTemplates: function(searchTerm = '') {
            const container = document.getElementById('tb-templates-list');
            let html = '';

            for (const [category, categoryData] of Object.entries(TABLE_TEMPLATES)) {
                let categoryHtml = '';
                let hasResults = false;

                for (const [templateName, fields] of Object.entries(categoryData.templates)) {
                    const matches = !searchTerm || 
                        category.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        templateName.toLowerCase().includes(searchTerm.toLowerCase());

                    if (matches) {
                        hasResults = true;
                        categoryHtml += `
                            <div class="tb-template-item" onclick="TableBuilder.applyTemplate('${category}', '${templateName}')">
                                <span class="tb-template-name">${templateName}</span>
                                <span class="tb-template-count">${fields.length} cols</span>
                            </div>
                        `;
                    }
                }

                if (hasResults) {
                    html += `
                        <div class="tb-category">
                            <div class="tb-category-header" style="border-left-color: ${categoryData.color}">
                                <span>${categoryData.icon}</span>
                                <span>${category}</span>
                            </div>
                            ${categoryHtml}
                        </div>
                    `;
                }
            }

            container.innerHTML = html || '<div class="tb-no-results">No templates found</div>';
        },

        /**
         * Filter templates by search term
         */
        filterTemplates: function(searchTerm) {
            this.renderTemplates(searchTerm);
        },

        /**
         * Apply a template
         */
        applyTemplate: function(category, templateName) {
            const template = TABLE_TEMPLATES[category]?.templates[templateName];
            if (!template) return;

            // Deep clone the template
            this.currentFields = JSON.parse(JSON.stringify(template));
            
            // Update table name
            this.tableName = templateName.toLowerCase().replace(/\s+/g, '_');
            document.getElementById('tb-table-name').value = this.tableName;

            this.renderFields();
            this.updateSQLPreview();

            if (window.VisualPrompter) {
                VisualPrompter.showToast(`Template "${templateName}" applied`, 'success');
            }
        },

        /**
         * Add a new field
         */
        addField: function(field = null) {
            const newField = field || {
                type: 'text',
                label: 'New Column',
                sqlType: 'VARCHAR',
                length: '255',
                icon: '📝',
                fieldName: 'new_column_' + (this.currentFields.length + 1),
                notNull: false,
                unique: false,
                primaryKey: false,
                autoIncrement: false,
                defaultValue: ''
            };

            this.currentFields.push(newField);
            this.renderFields();
            this.updateSQLPreview();
        },

        /**
         * Render fields list
         */
        renderFields: function() {
            const container = document.getElementById('tb-fields-list');
            const countSpan = document.getElementById('tb-field-count');
            
            countSpan.textContent = `(${this.currentFields.length})`;

            if (this.currentFields.length === 0) {
                container.innerHTML = '<div class="tb-no-fields">No columns yet. Select a template or add manually.</div>';
                return;
            }

            let html = '';
            this.currentFields.forEach((field, index) => {
                html += this.getFieldHTML(field, index);
            });

            container.innerHTML = html;

            // Bind field events
            this.bindFieldEvents();
        },

        /**
         * Get HTML for a single field
         */
        getFieldHTML: function(field, index) {
            const sqlTypeOptions = SQL_TYPES.map(t => 
                `<option value="${t.value}" ${field.sqlType === t.value ? 'selected' : ''}>${t.label}</option>`
            ).join('');

            return `
                <div class="tb-field-item" data-index="${index}">
                    <div class="tb-field-header">
                        <span class="tb-field-icon">${field.icon || '📝'}</span>
                        <input type="text" class="tb-field-name" value="${field.fieldName}" data-prop="fieldName" placeholder="column_name">
                        <button class="tb-field-delete" data-index="${index}" title="Delete">🗑️</button>
                    </div>
                    <div class="tb-field-options">
                        <div class="tb-field-option">
                            <label>Type</label>
                            <select class="tb-field-select" data-prop="sqlType">
                                ${sqlTypeOptions}
                            </select>
                        </div>
                        <div class="tb-field-option">
                            <label>Length</label>
                            <input type="text" class="tb-field-input" value="${field.length || ''}" data-prop="length" placeholder="255">
                        </div>
                        <div class="tb-field-option">
                            <label>Default</label>
                            <input type="text" class="tb-field-input" value="${field.defaultValue || ''}" data-prop="defaultValue" placeholder="NULL">
                        </div>
                    </div>
                    <div class="tb-field-checkboxes">
                        <label class="tb-checkbox">
                            <input type="checkbox" data-prop="primaryKey" ${field.primaryKey ? 'checked' : ''}>
                            <span>🔑 Primary</span>
                        </label>
                        <label class="tb-checkbox">
                            <input type="checkbox" data-prop="autoIncrement" ${field.autoIncrement ? 'checked' : ''}>
                            <span>🔄 Auto Inc</span>
                        </label>
                        <label class="tb-checkbox">
                            <input type="checkbox" data-prop="notNull" ${field.notNull ? 'checked' : ''}>
                            <span>❗ Not Null</span>
                        </label>
                        <label class="tb-checkbox">
                            <input type="checkbox" data-prop="unique" ${field.unique ? 'checked' : ''}>
                            <span>🎯 Unique</span>
                        </label>
                    </div>
                </div>
            `;
        },

        /**
         * Bind events for field items
         */
        bindFieldEvents: function() {
            const self = this;
            const container = document.getElementById('tb-fields-list');

            // Delete buttons
            container.querySelectorAll('.tb-field-delete').forEach(btn => {
                btn.addEventListener('click', function() {
                    const index = parseInt(this.dataset.index);
                    self.currentFields.splice(index, 1);
                    self.renderFields();
                    self.updateSQLPreview();
                });
            });

            // Field inputs and selects
            container.querySelectorAll('.tb-field-item').forEach(item => {
                const index = parseInt(item.dataset.index);

                // Text inputs and selects
                item.querySelectorAll('input[type="text"], select').forEach(input => {
                    input.addEventListener('change', function() {
                        self.currentFields[index][this.dataset.prop] = this.value;
                        self.updateSQLPreview();
                    });
                });

                // Checkboxes
                item.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        self.currentFields[index][this.dataset.prop] = this.checked;
                        self.updateSQLPreview();
                    });
                });
            });
        },

        /**
         * Generate SQL CREATE TABLE statement
         */
        generateSQL: function() {
            if (this.currentFields.length === 0) {
                return '-- Select a template or add columns';
            }

            const tableName = this.tableName || 'my_table';
            let sql = `CREATE TABLE IF NOT EXISTS \`${tableName}\` (\n`;
            
            const columnDefs = [];
            const primaryKeys = [];

            this.currentFields.forEach(field => {
                let def = `    \`${field.fieldName}\` ${field.sqlType}`;

                // Length
                if (field.length && field.sqlType !== 'TEXT' && field.sqlType !== 'LONGTEXT' && 
                    field.sqlType !== 'DATE' && field.sqlType !== 'DATETIME' && 
                    field.sqlType !== 'TIMESTAMP' && field.sqlType !== 'TIME' &&
                    field.sqlType !== 'BOOLEAN' && field.sqlType !== 'JSON') {
                    def += `(${field.length})`;
                }

                // NOT NULL
                if (field.notNull) {
                    def += ' NOT NULL';
                }

                // AUTO_INCREMENT
                if (field.autoIncrement) {
                    def += ' AUTO_INCREMENT';
                }

                // DEFAULT
                if (field.defaultValue) {
                    if (field.defaultValue === 'CURRENT_TIMESTAMP' || 
                        field.defaultValue.includes('CURRENT_TIMESTAMP')) {
                        def += ` DEFAULT ${field.defaultValue}`;
                    } else if (field.defaultValue === 'NULL') {
                        def += ' DEFAULT NULL';
                    } else {
                        def += ` DEFAULT '${field.defaultValue}'`;
                    }
                }

                // UNIQUE
                if (field.unique && !field.primaryKey) {
                    def += ' UNIQUE';
                }

                columnDefs.push(def);

                // Track primary keys
                if (field.primaryKey) {
                    primaryKeys.push(`\`${field.fieldName}\``);
                }
            });

            sql += columnDefs.join(',\n');

            // Add primary key constraint
            if (primaryKeys.length > 0) {
                sql += `,\n    PRIMARY KEY (${primaryKeys.join(', ')})`;
            }

            sql += '\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';

            return sql;
        },

        /**
         * Update SQL preview
         */
        updateSQLPreview: function() {
            const preview = document.getElementById('tb-sql-preview');
            preview.textContent = this.generateSQL();
        },

        /**
         * Generate table description text
         */
        generateTableDescription: function() {
            if (this.currentFields.length === 0) return '';

            const tableName = this.tableName || 'my_table';
            let desc = `Table: ${tableName}\n`;
            desc += `═══════════════════════════════════════\n`;
            desc += `Columns (${this.currentFields.length}):\n`;
            
            this.currentFields.forEach(field => {
                let line = `  • ${field.fieldName}: ${field.sqlType}`;
                if (field.length) line += `(${field.length})`;
                
                const flags = [];
                if (field.primaryKey) flags.push('PK');
                if (field.autoIncrement) flags.push('AI');
                if (field.notNull) flags.push('NN');
                if (field.unique) flags.push('UQ');
                
                if (flags.length > 0) line += ` [${flags.join(', ')}]`;
                if (field.defaultValue) line += ` = ${field.defaultValue}`;
                
                desc += line + '\n';
            });

            desc += `═══════════════════════════════════════\n\n`;
            desc += `SQL:\n${this.generateSQL()}`;

            return desc;
        },

        /**
         * Push table structure to description (Always appends)
         */
        pushToDescription: function() {
            if (!this.targetNode) return;

            if (this.currentFields.length === 0) {
                if (window.VisualPrompter) {
                    VisualPrompter.showToast('No columns to push', 'warning');
                }
                return;
            }

            // Get description textarea
            const descTextarea = document.querySelector('[data-key="description"]');
            if (!descTextarea) {
                // Store in node properties for later
                this.targetNode.properties.tableStructure = this.generateTableDescription();
                if (window.VisualPrompter) {
                    VisualPrompter.showToast('Table structure saved to node', 'success');
                }
                this.close();
                return;
            }

            const tableDesc = this.generateTableDescription();

            // Always APPEND to description (accumulate multiple tables)
            if (descTextarea.value.trim()) {
                descTextarea.value = descTextarea.value + '\n\n' + tableDesc;
            } else {
                descTextarea.value = tableDesc;
            }

            // Update node property
            this.targetNode.properties.description = descTextarea.value;

            // Also save table structure to tables array (accumulate)
            this.targetNode.properties.tables = this.targetNode.properties.tables || [];
            this.targetNode.properties.tables.push({
                name: this.tableName,
                columns: this.currentFields.map(f => ({
                    name: f.fieldName,
                    type: f.sqlType + (f.length ? `(${f.length})` : '')
                }))
            });

            if (window.VisualPrompter) {
                VisualPrompter.showToast('Table structure appended to description', 'success');
            }

            // Re-open popup to show updated content
            if (window.PopupEditor && this.targetNode) {
                PopupEditor.open(this.targetNode);
            }
        }
    };

    // Export to global
    global.TableBuilder = TableBuilder;

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        TableBuilder.init();
    });

})(typeof window !== 'undefined' ? window : this);

