/**
 * Visual Prompter - Main Application
 * A revolutionary visual-to-prompt design system
 */

(function(global) {
    'use strict';

    const VisualPrompter = {
        // Core properties
        graph: null,
        graphCanvas: null,
        projectName: 'Untitled Project',
        isDirty: false,

        // DOM Elements
        elements: {},

        /**
         * Initialize the application
         */
        init: function() {
            console.log('🎨 Visual Prompter Initializing...');
            
            // Cache DOM elements
            this.cacheElements();
            
            // Initialize particles background
            this.initParticles();
            
            // Initialize LiteGraph
            this.initGraph();
            
            // Initialize popup editor
            PopupEditor.init();
            
            // Bind events
            this.bindEvents();
            
            // Update status bar
            this.updateStatusBar();
            
            console.log('✨ Visual Prompter Ready!');
        },

        /**
         * Cache DOM elements for quick access
         */
        cacheElements: function() {
            this.elements = {
                canvas: document.getElementById('graph-canvas'),
                welcomeScreen: document.getElementById('welcome-screen'),
                nodeCount: document.getElementById('node-count'),
                connectionCount: document.getElementById('connection-count'),
                zoomLevel: document.getElementById('zoom-level'),
                projectName: document.getElementById('project-name'),
                promptModal: document.getElementById('prompt-modal'),
                saveModal: document.getElementById('save-modal'),
                promptPreview: document.getElementById('prompt-preview'),
                toastContainer: document.getElementById('toast-container'),
                fileInput: document.getElementById('file-input')
            };
        },

        /**
         * Initialize particles background
         */
        initParticles: function() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (10 + Math.random() * 10) + 's';
                particlesContainer.appendChild(particle);
            }
        },

        /**
         * Initialize LiteGraph canvas
         */
        initGraph: function() {
            // Create graph
            this.graph = new LGraph();
            
            // Create canvas
            this.graphCanvas = new LGraphCanvas(this.elements.canvas, this.graph);
            
            // Configure canvas
            this.graphCanvas.background_image = null;
            this.graphCanvas.clear_background = true;
            this.graphCanvas.render_shadows = true;
            this.graphCanvas.render_curved_connections = true;
            this.graphCanvas.render_connection_arrows = true;
            
            // Custom styling
            LiteGraph.NODE_DEFAULT_COLOR = '#1A1A2E';
            LiteGraph.NODE_DEFAULT_BGCOLOR = '#1A1A2E';
            LiteGraph.NODE_DEFAULT_BOXCOLOR = '#6366F1';
            LiteGraph.NODE_TITLE_COLOR = '#FFFFFF';
            LiteGraph.NODE_TEXT_COLOR = '#A0A0B0';
            LiteGraph.LINK_COLOR = '#6366F1';
            LiteGraph.CONNECTING_LINK_COLOR = '#8B5CF6';
            
            // Set canvas background
            this.graphCanvas.clear_background_color = '#0D0D1A';
            
            // Initialize Node Enhancer for interactive zones
            // (X to delete, drag header, double-click for form, connection slots)
            if (typeof NodeEnhancer !== 'undefined') {
                NodeEnhancer.init(this.graphCanvas, this.graph);
            }
            
            // Track changes
            const self = this;
            this.graph.onNodeAdded = function() {
                self.onGraphChanged();
            };
            
            this.graph.onNodeRemoved = function() {
                self.onGraphChanged();
            };
            
            this.graph.onConnectionChange = function() {
                self.onGraphChanged();
            };
            
            // Start rendering
            this.graph.start();
            
            // Resize handler
            window.addEventListener('resize', () => this.resizeCanvas());
            this.resizeCanvas();
        },

        /**
         * Resize canvas to fit container
         */
        resizeCanvas: function() {
            const container = this.elements.canvas.parentElement;
            this.elements.canvas.width = container.clientWidth;
            this.elements.canvas.height = container.clientHeight;
            this.graphCanvas.resize();
        },

        /**
         * Handle graph changes
         */
        onGraphChanged: function() {
            this.isDirty = true;
            this.updateStatusBar();
            this.checkWelcomeScreen();
        },

        /**
         * Check if welcome screen should be shown
         */
        checkWelcomeScreen: function() {
            const nodes = this.graph._nodes || [];
            if (nodes.length > 0) {
                this.elements.welcomeScreen.classList.add('hidden');
            } else {
                this.elements.welcomeScreen.classList.remove('hidden');
            }
        },

        /**
         * Update status bar information
         */
        updateStatusBar: function() {
            const nodes = this.graph._nodes || [];
            const links = Object.keys(this.graph.links || {}).length;
            const zoom = Math.round(this.graphCanvas.ds.scale * 100);
            
            this.elements.nodeCount.textContent = nodes.length;
            this.elements.connectionCount.textContent = links;
            this.elements.zoomLevel.textContent = zoom + '%';
            this.elements.projectName.textContent = this.projectName + (this.isDirty ? ' •' : '');
        },

        /**
         * Bind all event listeners
         */
        bindEvents: function() {
            const self = this;

            // Shape buttons - Add nodes
            document.querySelectorAll('.shape-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    self.addNode(this.dataset.node);
                });
            });

            // Welcome screen buttons
            document.getElementById('btn-new-project').addEventListener('click', () => this.newProject());
            document.getElementById('btn-load-project').addEventListener('click', () => this.loadProject());

            // Toolbar buttons
            document.getElementById('btn-save').addEventListener('click', () => this.showSaveModal());
            document.getElementById('btn-load').addEventListener('click', () => this.loadProject());
            document.getElementById('btn-undo').addEventListener('click', () => this.undo());
            document.getElementById('btn-redo').addEventListener('click', () => this.redo());
            document.getElementById('btn-clear').addEventListener('click', () => this.clearCanvas());
            document.getElementById('btn-generate').addEventListener('click', () => this.generatePrompt());

            // Export dropdown items
            document.querySelectorAll('[data-export]').forEach(btn => {
                btn.addEventListener('click', function() {
                    self.handleExport(this.dataset.export);
                });
            });

            // Zoom controls
            document.getElementById('btn-zoom-in').addEventListener('click', () => this.zoom(1.2));
            document.getElementById('btn-zoom-out').addEventListener('click', () => this.zoom(0.8));
            document.getElementById('btn-zoom-reset').addEventListener('click', () => this.zoomReset());

            // Toggle buttons
            document.getElementById('btn-grid').addEventListener('click', function() {
                this.classList.toggle('active');
                self.toggleGrid();
            });
            document.getElementById('btn-minimap').addEventListener('click', function() {
                this.classList.toggle('active');
                self.toggleMinimap();
            });

            // Save modal
            document.getElementById('close-save-modal').addEventListener('click', () => this.closeSaveModal());
            document.getElementById('btn-cancel-save').addEventListener('click', () => this.closeSaveModal());
            document.getElementById('btn-confirm-save').addEventListener('click', () => this.saveProject());

            // Prompt modal
            document.getElementById('close-prompt-modal').addEventListener('click', () => this.closePromptModal());
            document.getElementById('btn-close-continue').addEventListener('click', () => this.closePromptModal());
            document.getElementById('btn-copy-prompt').addEventListener('click', () => this.copyPrompt());
            document.getElementById('btn-download-txt').addEventListener('click', () => this.downloadPrompt('txt'));
            document.getElementById('btn-download-md').addEventListener('click', () => this.downloadPrompt('md'));

            // File input
            this.elements.fileInput.addEventListener('change', (e) => this.handleFileLoad(e));

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => this.handleKeyboard(e));
        },

        /**
         * Add a new node to the canvas
         * @param {string} nodeType - Type of node to add
         */
        addNode: function(nodeType) {
            const nodeTypes = {
                database: 'VisualPrompter/Database',
                backend: 'VisualPrompter/Backend',
                frontend: 'VisualPrompter/Frontend',
                api: 'VisualPrompter/API',
                process: 'VisualPrompter/Process',
                decision: 'VisualPrompter/Decision',
                service: 'VisualPrompter/Service'
            };

            const type = nodeTypes[nodeType];
            if (!type) return;

            // Get center of visible canvas
            const canvas = this.graphCanvas;
            const centerX = (-canvas.ds.offset[0] + canvas.canvas.width / 2) / canvas.ds.scale;
            const centerY = (-canvas.ds.offset[1] + canvas.canvas.height / 2) / canvas.ds.scale;

            // Add some randomness to avoid stacking
            const x = centerX + (Math.random() - 0.5) * 200;
            const y = centerY + (Math.random() - 0.5) * 200;

            // Create node
            const node = LiteGraph.createNode(type);
            if (node) {
                node.pos = [x, y];
                this.graph.add(node);
                
                // Select the new node
                this.graphCanvas.selectNode(node);
                
                this.showToast(`${nodeType.charAt(0).toUpperCase() + nodeType.slice(1)} node added - Double-click to edit`, 'success');
            }
        },

        /**
         * Create new project
         */
        newProject: function() {
            const self = this;
            
            const createNew = () => {
                self.graph.clear();
                self.projectName = 'Untitled Project';
                self.isDirty = false;
                self.updateStatusBar();
                self.checkWelcomeScreen();
                self.showToast('New project created', 'success');
            };
            
            if (this.isDirty) {
                this.showConfirm({
                    title: 'Unsaved Changes',
                    message: 'You have unsaved changes. Create new project anyway?',
                    confirmText: 'Create New',
                    cancelText: 'Cancel',
                    type: 'warning'
                }).then(confirmed => {
                    if (confirmed) createNew();
                });
            } else {
                createNew();
            }
        },

        /**
         * Load project from file
         */
        loadProject: function() {
            this.elements.fileInput.click();
        },

        /**
         * Handle file load
         */
        handleFileLoad: function(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const data = JSON.parse(e.target.result);
                    this.loadProjectData(data);
                    this.showToast('Project loaded successfully', 'success');
                    
                    // Auto-generate prompt after loading
                    setTimeout(() => this.generatePrompt(), 500);
                } catch (error) {
                    console.error('Error loading project:', error);
                    this.showToast('Failed to load project', 'error');
                }
            };
            reader.readAsText(file);
            
            // Reset file input
            event.target.value = '';
        },

        /**
         * Load project data into graph
         */
        loadProjectData: function(data) {
            this.graph.clear();
            
            // Set project name
            this.projectName = data.projectName || 'Loaded Project';
            
            // Configure graph from data
            if (data.graph) {
                this.graph.configure(data.graph);
            }
            
            this.isDirty = false;
            this.updateStatusBar();
            this.checkWelcomeScreen();
        },

        /**
         * Show save modal
         */
        showSaveModal: function() {
            document.getElementById('save-project-name').value = this.projectName;
            this.elements.saveModal.classList.add('active');
        },

        /**
         * Close save modal
         */
        closeSaveModal: function() {
            this.elements.saveModal.classList.remove('active');
        },

        /**
         * Save project to file
         */
        saveProject: function() {
            const projectName = document.getElementById('save-project-name').value.trim() || 'Untitled Project';
            this.projectName = projectName;
            
            const data = {
                version: '1.0',
                projectName: projectName,
                createdAt: new Date().toISOString(),
                graph: this.graph.serialize()
            };

            // Download as JSON
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = projectName.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.json';
            a.click();
            URL.revokeObjectURL(url);

            this.isDirty = false;
            this.updateStatusBar();
            this.closeSaveModal();
            this.showToast('Project saved!', 'success');
            
            // Confetti effect
            this.showConfetti();
        },

        /**
         * Generate AI prompt from diagram
         */
        generatePrompt: function() {
            const prompt = PromptGenerator.generate(this.graph, this.projectName);
            
            // Update modal content
            document.getElementById('prompt-project-name').textContent = 'Project: ' + this.projectName;
            document.getElementById('prompt-stats').textContent = 
                `Nodes: ${(this.graph._nodes || []).length} │ Connections: ${Object.keys(this.graph.links || {}).length}`;
            document.getElementById('prompt-timestamp').textContent = 
                'Generated: ' + new Date().toLocaleString();
            
            // Display prompt with markdown-like formatting
            this.elements.promptPreview.innerHTML = this.formatPromptForDisplay(prompt);
            
            // Show modal
            this.elements.promptModal.classList.add('active');
            
            // Sparkle effect
            this.showSparkle();
        },

        /**
         * Format prompt for display in modal
         */
        formatPromptForDisplay: function(prompt) {
            // Simple markdown-like formatting
            return prompt
                .replace(/^# (.+)$/gm, '<h1>$1</h1>')
                .replace(/^## (.+)$/gm, '<h2>$1</h2>')
                .replace(/^### (.+)$/gm, '<h3>$3</h3>')
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>')
                .replace(/`(.+?)`/g, '<code>$1</code>')
                .replace(/^- (.+)$/gm, '• $1')
                .replace(/^> (.+)$/gm, '<blockquote>$1</blockquote>')
                .replace(/```(\w+)?\n([\s\S]*?)```/g, '<pre><code>$2</code></pre>')
                .replace(/\n/g, '<br>');
        },

        /**
         * Close prompt modal
         */
        closePromptModal: function() {
            this.elements.promptModal.classList.remove('active');
        },

        /**
         * Copy prompt to clipboard
         */
        copyPrompt: function() {
            const prompt = PromptGenerator.generate(this.graph, this.projectName);
            navigator.clipboard.writeText(prompt).then(() => {
                this.showToast('Prompt copied to clipboard!', 'success');
            }).catch(() => {
                this.showToast('Failed to copy prompt', 'error');
            });
        },

        /**
         * Download prompt as file
         */
        downloadPrompt: function(format) {
            const prompt = PromptGenerator.generate(this.graph, this.projectName);
            const filename = this.projectName.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '_prompt.' + format;
            
            const blob = new Blob([prompt], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);
            
            this.showToast(`Prompt downloaded as ${format.toUpperCase()}`, 'success');
        },

        /**
         * Handle export action
         */
        handleExport: function(format) {
            switch (format) {
                case 'json':
                    this.showSaveModal();
                    break;
                case 'txt':
                case 'md':
                    this.downloadPrompt(format);
                    break;
                case 'png':
                case 'svg':
                    this.exportDiagram(format);
                    break;
            }
        },

        /**
         * Export diagram as image
         */
        exportDiagram: function(format) {
            // For PNG/SVG export, we'll use canvas2image approach
            const canvas = this.elements.canvas;
            
            if (format === 'png') {
                canvas.toBlob((blob) => {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = this.projectName.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.png';
                    a.click();
                    URL.revokeObjectURL(url);
                    this.showToast('Diagram exported as PNG', 'success');
                });
            } else {
                // SVG export would require additional library
                this.showToast('SVG export coming soon!', 'info');
            }
        },

        /**
         * Zoom canvas
         */
        zoom: function(factor) {
            this.graphCanvas.ds.scale *= factor;
            this.graphCanvas.ds.scale = Math.max(0.1, Math.min(3, this.graphCanvas.ds.scale));
            this.graphCanvas.setDirty(true, true);
            this.updateStatusBar();
        },

        /**
         * Reset zoom to 100%
         */
        zoomReset: function() {
            this.graphCanvas.ds.scale = 1;
            this.graphCanvas.ds.offset = [0, 0];
            this.graphCanvas.setDirty(true, true);
            this.updateStatusBar();
        },

        /**
         * Toggle grid
         */
        toggleGrid: function() {
            this.graphCanvas.render_grid = !this.graphCanvas.render_grid;
            this.graphCanvas.setDirty(true, true);
        },

        /**
         * Toggle minimap
         */
        toggleMinimap: function() {
            // Minimap would require additional implementation
            this.showToast('Minimap coming soon!', 'info');
        },

        /**
         * Undo action
         */
        undo: function() {
            // LiteGraph doesn't have built-in undo, would need custom implementation
            this.showToast('Undo coming soon!', 'info');
        },

        /**
         * Redo action
         */
        redo: function() {
            this.showToast('Redo coming soon!', 'info');
        },

        /**
         * Clear all nodes and connections from canvas
         */
        clearCanvas: function() {
            const self = this;
            const nodes = this.graph._nodes || [];
            if (nodes.length === 0) {
                this.showToast('Canvas is already empty', 'info');
                return;
            }

            this.showConfirm({
                title: 'Clear Canvas',
                message: 'Are you sure you want to clear all nodes and connections? This cannot be undone.',
                confirmText: 'Clear All',
                cancelText: 'Cancel',
                type: 'danger'
            }).then(confirmed => {
                if (confirmed) {
                    self.graph.clear();
                    self.isDirty = false;
                    self.updateStatusBar();
                    self.checkWelcomeScreen();
                    self.showToast('Canvas cleared', 'success');
                }
            });
        },

        /**
         * Handle keyboard shortcuts
         */
        handleKeyboard: function(e) {
            // Don't trigger shortcuts when typing in input fields
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }

            // Ctrl/Cmd + Key combinations
            if (e.ctrlKey || e.metaKey) {
                switch (e.key.toLowerCase()) {
                    case 's':
                        e.preventDefault();
                        this.showSaveModal();
                        break;
                    case 'o':
                        e.preventDefault();
                        this.loadProject();
                        break;
                    case 'g':
                        e.preventDefault();
                        this.generatePrompt();
                        break;
                    case 'z':
                        e.preventDefault();
                        this.undo();
                        break;
                    case 'y':
                        e.preventDefault();
                        this.redo();
                        break;
                    case 'Delete':
                        e.preventDefault();
                        this.clearCanvas();
                        break;
                }
            }

            // Single key shortcuts
            switch (e.key) {
                case '1': this.addNode('database'); break;
                case '2': this.addNode('backend'); break;
                case '3': this.addNode('frontend'); break;
                case '4': this.addNode('api'); break;
                case '5': this.addNode('process'); break;
                case '6': this.addNode('decision'); break;
                case '7': this.addNode('service'); break;
                case 'g':
                case 'G':
                    if (!e.ctrlKey && !e.metaKey) {
                        document.getElementById('btn-grid').click();
                    }
                    break;
                case 'm':
                case 'M':
                    document.getElementById('btn-minimap').click();
                    break;
                case 'Delete':
                case 'Backspace':
                    // Delete selected node
                    const selected = this.graphCanvas.selected_nodes;
                    if (selected && Object.keys(selected).length > 0) {
                        const count = Object.keys(selected).length;
                        this.showConfirm({
                            title: 'Delete Nodes',
                            message: `Are you sure you want to delete ${count} selected node(s)?`,
                            confirmText: 'Delete',
                            cancelText: 'Cancel',
                            type: 'danger'
                        }).then(confirmed => {
                            if (confirmed) {
                                for (let id in selected) {
                                    this.graph.remove(selected[id]);
                                }
                                this.showToast('Nodes deleted', 'info');
                            }
                        });
                    }
                    break;
            }
        },

        /**
         * Show custom confirm dialog (replaces native confirm)
         * @param {Object} options - Dialog options
         * @param {string} options.title - Dialog title
         * @param {string} options.message - Dialog message
         * @param {string} options.confirmText - Confirm button text
         * @param {string} options.cancelText - Cancel button text
         * @param {string} options.type - Dialog type: 'danger', 'warning', 'info'
         * @returns {Promise<boolean>} - Resolves true if confirmed, false if cancelled
         */
        showConfirm: function(options = {}) {
            return new Promise((resolve) => {
                const {
                    title = 'Confirm',
                    message = 'Are you sure?',
                    confirmText = 'Confirm',
                    cancelText = 'Cancel',
                    type = 'danger'
                } = options;

                const icons = {
                    danger: '🗑️',
                    warning: '⚠️',
                    info: 'ℹ️'
                };

                // Create overlay
                const overlay = document.createElement('div');
                overlay.className = 'confirm-overlay';
                overlay.innerHTML = `
                    <div class="confirm-dialog ${type}">
                        <div class="confirm-header">
                            <span class="confirm-icon">${icons[type]}</span>
                            <h3 class="confirm-title">${title}</h3>
                        </div>
                        <p class="confirm-message">${message}</p>
                        <div class="confirm-actions">
                            <button class="confirm-btn cancel">${cancelText}</button>
                            <button class="confirm-btn confirm ${type}">${confirmText}</button>
                        </div>
                    </div>
                `;

                document.body.appendChild(overlay);

                // Animate in
                requestAnimationFrame(() => {
                    overlay.classList.add('active');
                });

                // Handle buttons
                const confirmBtn = overlay.querySelector('.confirm-btn.confirm');
                const cancelBtn = overlay.querySelector('.confirm-btn.cancel');

                const closeDialog = (result) => {
                    overlay.classList.remove('active');
                    setTimeout(() => {
                        overlay.remove();
                        resolve(result);
                    }, 200);
                };

                confirmBtn.addEventListener('click', () => closeDialog(true));
                cancelBtn.addEventListener('click', () => closeDialog(false));

                // Close on overlay click
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) closeDialog(false);
                });

                // Close on Escape key
                const handleEscape = (e) => {
                    if (e.key === 'Escape') {
                        document.removeEventListener('keydown', handleEscape);
                        closeDialog(false);
                    }
                };
                document.addEventListener('keydown', handleEscape);

                // Focus confirm button
                confirmBtn.focus();
            });
        },

        /**
         * Show toast notification
         */
        showToast: function(message, type = 'info') {
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };

            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <span class="toast-icon">${icons[type]}</span>
                <span class="toast-message">${message}</span>
                <button class="toast-close">×</button>
            `;

            this.elements.toastContainer.appendChild(toast);

            // Close button
            toast.querySelector('.toast-close').addEventListener('click', () => {
                toast.remove();
            });

            // Auto remove after 4 seconds
            setTimeout(() => {
                toast.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        },

        /**
         * Show confetti effect
         */
        showConfetti: function() {
            const colors = ['#6366F1', '#8B5CF6', '#EC4899', '#06B6D4', '#10B981', '#F59E0B'];
            
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = '-10px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.transform = `rotate(${Math.random() * 360}deg)`;
                confetti.style.animation = `fall ${2 + Math.random() * 2}s linear forwards`;
                document.body.appendChild(confetti);
                
                setTimeout(() => confetti.remove(), 4000);
            }

            // Add confetti animation
            if (!document.getElementById('confetti-style')) {
                const style = document.createElement('style');
                style.id = 'confetti-style';
                style.textContent = `
                    @keyframes fall {
                        to {
                            transform: translateY(100vh) rotate(720deg);
                            opacity: 0;
                        }
                    }
                `;
                document.head.appendChild(style);
            }
        },

        /**
         * Show sparkle effect
         */
        showSparkle: function() {
            const btn = document.getElementById('btn-generate');
            btn.style.animation = 'none';
            setTimeout(() => {
                btn.style.animation = 'pulse-glow 3s ease-in-out infinite';
            }, 10);
        }
    };

    // Export to global
    global.VisualPrompter = VisualPrompter;

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        VisualPrompter.init();
    });

})(typeof window !== 'undefined' ? window : this);

