<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visual Prompter - AI Prompt Generator</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- LiteGraph.js -->
    <link rel="stylesheet" href="https://unpkg.com/litegraph.js/css/litegraph.css">
    <script src="https://unpkg.com/litegraph.js/build/litegraph.min.js"></script>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="visual-prompter/css/style.css">
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="aurora"></div>
        <div class="particles" id="particles"></div>
    </div>

    <!-- Main Container -->
    <div class="app-container">
        <!-- Top Toolbar -->
        <header class="toolbar">
            <div class="toolbar-left">
                <div class="logo">
                    <span class="logo-icon">✨</span>
                    <span class="logo-text">Visual Prompter</span>
                </div>
            </div>
            
            <div class="toolbar-center">
                <!-- Shape Buttons -->
                <div class="shape-buttons">
                    <button class="shape-btn" data-node="database" title="Database (1)">
                        <span class="shape-icon database-icon">⬡</span>
                        <span class="shape-label">Database</span>
                    </button>
                    <button class="shape-btn" data-node="backend" title="Backend (2)">
                        <span class="shape-icon backend-icon">◼</span>
                        <span class="shape-label">Backend</span>
                    </button>
                    <button class="shape-btn" data-node="frontend" title="Frontend (3)">
                        <span class="shape-icon frontend-icon">▭</span>
                        <span class="shape-label">Frontend</span>
                    </button>
                    <button class="shape-btn" data-node="api" title="API (4)">
                        <span class="shape-icon api-icon">▲</span>
                        <span class="shape-label">API</span>
                    </button>
                    <button class="shape-btn" data-node="process" title="Process (5)">
                        <span class="shape-icon process-icon">●</span>
                        <span class="shape-label">Process</span>
                    </button>
                    <button class="shape-btn" data-node="decision" title="Decision (6)">
                        <span class="shape-icon decision-icon">◆</span>
                        <span class="shape-label">Decision</span>
                    </button>
                    <button class="shape-btn" data-node="service" title="Service (7)">
                        <span class="shape-icon service-icon">◎</span>
                        <span class="shape-label">Service</span>
                    </button>
                </div>
                
                <div class="toolbar-divider"></div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="action-btn" id="btn-save" title="Save Project (Ctrl+S)">
                        <span>💾</span>
                    </button>
                    <button class="action-btn" id="btn-load" title="Load Project (Ctrl+O)">
                        <span>📂</span>
                    </button>
                    <div class="dropdown">
                        <button class="action-btn" id="btn-export" title="Export (Ctrl+E)">
                            <span>📤</span>
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="dropdown-menu" id="export-menu">
                            <div class="dropdown-header">Export Diagram</div>
                            <button class="dropdown-item" data-export="png">
                                <span>🖼️</span> As PNG Image
                            </button>
                            <button class="dropdown-item" data-export="svg">
                                <span>📐</span> As SVG Vector
                            </button>
                            <button class="dropdown-item" data-export="json">
                                <span>📋</span> As JSON Project
                            </button>
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-header">Export Prompt</div>
                            <button class="dropdown-item" data-export="txt">
                                <span>📝</span> As TXT File
                            </button>
                            <button class="dropdown-item" data-export="md">
                                <span>📄</span> As Markdown
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="toolbar-divider"></div>
                
                <!-- Undo/Redo -->
                <div class="history-buttons">
                    <button class="action-btn" id="btn-undo" title="Undo (Ctrl+Z)">
                        <span>↩️</span>
                    </button>
                    <button class="action-btn" id="btn-redo" title="Redo (Ctrl+Y)">
                        <span>↪️</span>
                    </button>
                </div>
                
                <div class="toolbar-divider"></div>
                
                <!-- Clear Canvas -->
                <div class="clear-buttons">
                    <button class="action-btn danger" id="btn-clear" title="Clear Canvas (Ctrl+Del)">
                        <span>🗑️</span>
                    </button>
                </div>
            </div>
            
            <div class="toolbar-right">
                <button class="generate-btn" id="btn-generate" title="Generate Prompt (Ctrl+G)">
                    <span class="generate-icon">✨</span>
                    <span class="generate-text">Generate Prompt</span>
                    <div class="generate-glow"></div>
                </button>
            </div>
        </header>

        <!-- Canvas Container -->
        <main class="canvas-container">
            <canvas id="graph-canvas"></canvas>
            
            <!-- Welcome Screen (shown when empty) -->
            <div class="welcome-screen" id="welcome-screen">
                <div class="welcome-content">
                    <div class="welcome-icon">
                        <span>🎨</span>
                    </div>
                    <h1 class="welcome-title">Welcome to Visual Prompter</h1>
                    <p class="welcome-subtitle">Design your AI prompts visually</p>
                    <div class="welcome-actions">
                        <button class="welcome-btn primary" id="btn-new-project">
                            <span>➕</span> New Project
                        </button>
                        <button class="welcome-btn secondary" id="btn-load-project">
                            <span>📂</span> Load Project
                        </button>
                    </div>
                    <div class="welcome-hint">
                        <p>💡 Tip: Drag shapes from the toolbar to create nodes, then connect them!</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Bottom Status Bar -->
        <footer class="status-bar">
            <div class="status-left">
                <span class="status-item">
                    <span class="status-icon">📦</span>
                    <span>Nodes: <strong id="node-count">0</strong></span>
                </span>
                <span class="status-divider">│</span>
                <span class="status-item">
                    <span class="status-icon">🔗</span>
                    <span>Connections: <strong id="connection-count">0</strong></span>
                </span>
            </div>
            <div class="status-center">
                <span class="status-item project-name" id="project-name">Untitled Project</span>
            </div>
            <div class="status-right">
                <span class="status-item">
                    <span>Zoom: <strong id="zoom-level">100%</strong></span>
                </span>
                <div class="zoom-controls">
                    <button class="zoom-btn" id="btn-zoom-out" title="Zoom Out">−</button>
                    <button class="zoom-btn" id="btn-zoom-reset" title="Reset Zoom">⊙</button>
                    <button class="zoom-btn" id="btn-zoom-in" title="Zoom In">+</button>
                </div>
                <span class="status-divider">│</span>
                <button class="status-toggle" id="btn-grid" title="Toggle Grid (G)">
                    <span>🔲</span> Grid
                </button>
                <button class="status-toggle" id="btn-minimap" title="Toggle Minimap (M)">
                    <span>📍</span> Minimap
                </button>
            </div>
        </footer>
    </div>

    <!-- Node Popup Template -->
    <div class="popup-overlay" id="popup-overlay">
        <div class="popup-container" id="popup-container">
            <!-- Popup content will be dynamically inserted here -->
        </div>
    </div>

    <!-- Generated Prompt Modal -->
    <div class="modal-overlay" id="prompt-modal">
        <div class="modal-container prompt-modal-container">
            <div class="modal-header">
                <div class="modal-title">
                    <span class="modal-icon">✨</span>
                    <span>Generated AI Prompt</span>
                </div>
                <button class="modal-close" id="close-prompt-modal">×</button>
            </div>
            <div class="modal-meta">
                <span id="prompt-project-name">Project: Untitled</span>
                <span class="meta-divider">│</span>
                <span id="prompt-stats">Nodes: 0 │ Connections: 0</span>
                <span class="meta-divider">│</span>
                <span id="prompt-timestamp">Generated: --</span>
            </div>
            <div class="modal-body">
                <div class="prompt-preview" id="prompt-preview">
                    <!-- Generated prompt will appear here -->
                </div>
            </div>
            <div class="modal-actions">
                <button class="modal-btn primary" id="btn-copy-prompt">
                    <span>📋</span> Copy to Clipboard
                </button>
                <button class="modal-btn secondary" id="btn-download-txt">
                    <span>💾</span> Download .txt
                </button>
                <button class="modal-btn secondary" id="btn-download-md">
                    <span>📄</span> Download .md
                </button>
                <button class="modal-btn ghost" id="btn-close-continue">
                    Close & Continue
                </button>
            </div>
        </div>
    </div>

    <!-- Save Project Modal -->
    <div class="modal-overlay" id="save-modal">
        <div class="modal-container save-modal-container">
            <div class="modal-header">
                <div class="modal-title">
                    <span class="modal-icon">💾</span>
                    <span>Save Project</span>
                </div>
                <button class="modal-close" id="close-save-modal">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="save-project-name">Project Name</label>
                    <input type="text" id="save-project-name" class="form-input" placeholder="My Awesome Project" autofocus>
                </div>
            </div>
            <div class="modal-actions">
                <button class="modal-btn primary" id="btn-confirm-save">
                    <span>💾</span> Save Project
                </button>
                <button class="modal-btn ghost" id="btn-cancel-save">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-container" id="toast-container"></div>

    <!-- Hidden File Input -->
    <input type="file" id="file-input" accept=".json" style="display: none;">

    <!-- Scripts -->
    <script src="visual-prompter/js/nodes/DatabaseNode.js"></script>
    <script src="visual-prompter/js/nodes/BackendNode.js"></script>
    <script src="visual-prompter/js/nodes/FrontendNode.js"></script>
    <script src="visual-prompter/js/nodes/APINode.js"></script>
    <script src="visual-prompter/js/nodes/ProcessNode.js"></script>
    <script src="visual-prompter/js/nodes/DecisionNode.js"></script>
    <script src="visual-prompter/js/nodes/ServiceNode.js"></script>
    <script src="visual-prompter/js/popup.js"></script>
    <script src="visual-prompter/js/prompt-generator.js"></script>
    <script src="visual-prompter/js/app.js"></script>

<!-- Back to Catalog Button -->
<a href="index.php" id="backToCatalogBtn" class="catalog-back-btn" style="position: fixed; bottom: 30px; left: 30px; width: 70px; height: 70px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 25px rgba(240, 147, 251, 0.5); z-index: 9999; text-decoration: none; transition: all 0.3s ease; border: 3px solid rgba(255, 255, 255, 0.3); animation: catalog-pulse 2s infinite;" title="Back to Catalog" onmouseover="this.style.transform='scale(1.15) rotate(-10deg)'; this.style.boxShadow='0 10px 35px rgba(240, 147, 251, 0.7)';" onmouseout="this.style.transform='scale(1) rotate(0deg)'; this.style.boxShadow='0 8px 25px rgba(240, 147, 251, 0.5)';">
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
        <polyline points="9 22 9 12 15 12 15 22"></polyline>
    </svg>
</a>
<style>
@keyframes catalog-pulse {
    0%, 100% { box-shadow: 0 8px 25px rgba(240, 147, 251, 0.5), 0 0 0 0 rgba(240, 147, 251, 0.4); }
    50% { box-shadow: 0 8px 25px rgba(240, 147, 251, 0.5), 0 0 0 10px rgba(240, 147, 251, 0); }
}

@keyframes logoFloat {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    25% { transform: translateY(-8px) rotate(-2deg); }
    50% { transform: translateY(-12px) rotate(0deg); }
    75% { transform: translateY(-8px) rotate(2deg); }
}
.catalog-back-btn::after {
    content: 'Catalog';
    position: absolute;
    left: 85px;
    background: rgba(0, 0, 0, 0.85);
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.catalog-back-btn:hover::after {
    opacity: 1;
}
</style>
<!-- End Back to Catalog Button -->
</body>
</html>

