/**
 * Node Enhancer - Adds interactive zones to canvas nodes
 * 
 * Each node on canvas will have:
 * - X button (top-right) to delete the node
 * - Header zone (grab cursor) for dragging
 * - Body zone (double-click) to open form
 * - Connection slots (crosshair cursor) for linking
 */

(function(global) {
    'use strict';

    const NodeEnhancer = {
        // Zone dimensions
        closeButtonSize: 20,
        headerHeight: 30,
        
        /**
         * Initialize the node enhancer
         * @param {LGraphCanvas} graphCanvas - The LiteGraph canvas instance
         * @param {LGraph} graph - The LiteGraph instance
         */
        init: function(graphCanvas, graph) {
            this.graphCanvas = graphCanvas;
            this.graph = graph;
            
            // Store original methods
            this.originalDrawNode = graphCanvas.drawNode;
            this.originalProcessMouseDown = graphCanvas.processMouseDown;
            this.originalOnMouseMove = graphCanvas.onMouseMove;
            
            // Bind enhanced methods
            this.bindDrawNode();
            this.bindMouseEvents();
            
            console.log('🎯 Node Enhancer initialized');
        },

        /**
         * Bind enhanced node drawing
         */
        bindDrawNode: function() {
            const self = this;
            const canvas = this.graphCanvas;
            
            // Override the node drawing to add our UI elements
            const originalDrawNodeShape = LGraphCanvas.prototype.drawNodeShape;
            
            LGraphCanvas.prototype.drawNodeShape = function(node, ctx, size, fgcolor, bgcolor, selected, mouse_over) {
                // Call original drawing
                originalDrawNodeShape.call(this, node, ctx, size, fgcolor, bgcolor, selected, mouse_over);
                
                // Add our custom UI elements
                self.drawNodeEnhancements(node, ctx, size, selected, mouse_over);
            };
        },

        /**
         * Draw enhanced UI elements on node
         */
        drawNodeEnhancements: function(node, ctx, size, selected, mouse_over) {
            if (node.flags && node.flags.collapsed) return;
            
            const closeSize = this.closeButtonSize;
            const nodeWidth = size[0];
            
            // Draw close (X) button in top-right corner
            ctx.save();
            
            // Close button background
            const closeX = nodeWidth - closeSize - 5;
            const closeY = -LiteGraph.NODE_TITLE_HEIGHT + 5;
            
            // Button circle
            ctx.beginPath();
            ctx.arc(closeX + closeSize/2, closeY + closeSize/2, closeSize/2, 0, Math.PI * 2);
            
            if (mouse_over && this.isOverCloseButton(node)) {
                ctx.fillStyle = 'rgba(239, 68, 68, 0.9)'; // Red on hover
            } else {
                ctx.fillStyle = 'rgba(239, 68, 68, 0.5)'; // Semi-transparent red
            }
            ctx.fill();
            
            // X icon
            ctx.strokeStyle = '#FFFFFF';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            
            const padding = 5;
            ctx.beginPath();
            ctx.moveTo(closeX + padding, closeY + padding);
            ctx.lineTo(closeX + closeSize - padding, closeY + closeSize - padding);
            ctx.moveTo(closeX + closeSize - padding, closeY + padding);
            ctx.lineTo(closeX + padding, closeY + closeSize - padding);
            ctx.stroke();
            
            // Draw zone indicators when hovered
            if (mouse_over && selected) {
                this.drawZoneIndicators(node, ctx, size);
            }
            
            ctx.restore();
        },

        /**
         * Draw visual zone indicators
         */
        drawZoneIndicators: function(node, ctx, size) {
            const nodeWidth = size[0];
            const nodeHeight = size[1];
            
            // Header zone indicator (drag)
            ctx.fillStyle = 'rgba(99, 102, 241, 0.1)';
            ctx.fillRect(0, -LiteGraph.NODE_TITLE_HEIGHT, nodeWidth - this.closeButtonSize - 10, LiteGraph.NODE_TITLE_HEIGHT);
            
            // Body zone indicator (double-click for form)
            ctx.fillStyle = 'rgba(139, 92, 246, 0.05)';
            ctx.fillRect(0, 0, nodeWidth, nodeHeight);
        },

        /**
         * Check if mouse is over close button
         */
        isOverCloseButton: function(node) {
            if (!this._lastMousePos) return false;
            
            const closeSize = this.closeButtonSize;
            const nodeWidth = node.size[0];
            const closeX = node.pos[0] + nodeWidth - closeSize - 5;
            const closeY = node.pos[1] - LiteGraph.NODE_TITLE_HEIGHT + 5;
            
            const mx = this._lastMousePos[0];
            const my = this._lastMousePos[1];
            
            return mx >= closeX && mx <= closeX + closeSize &&
                   my >= closeY && my <= closeY + closeSize;
        },

        /**
         * Get which zone the mouse is in
         */
        getMouseZone: function(node, localX, localY) {
            const closeSize = this.closeButtonSize;
            const nodeWidth = node.size[0];
            const nodeHeight = node.size[1];
            
            // Check close button
            const closeX = nodeWidth - closeSize - 5;
            const closeY = -LiteGraph.NODE_TITLE_HEIGHT + 5;
            
            if (localX >= closeX && localX <= closeX + closeSize &&
                localY >= closeY && localY <= closeY + closeSize) {
                return 'close';
            }
            
            // Check header (title bar) - drag zone
            if (localY < 0 && localY >= -LiteGraph.NODE_TITLE_HEIGHT) {
                return 'header';
            }
            
            // Check connection slots area (left and right edges)
            if (localX < 15 || localX > nodeWidth - 15) {
                return 'connection';
            }
            
            // Body area - form zone
            if (localY >= 0 && localY <= nodeHeight) {
                return 'body';
            }
            
            return 'outside';
        },

        /**
         * Bind mouse event handlers
         */
        bindMouseEvents: function() {
            const self = this;
            const canvas = this.graphCanvas;
            const canvasElement = canvas.canvas;
            
            // Track mouse position for zone detection
            canvasElement.addEventListener('mousemove', function(e) {
                const rect = canvasElement.getBoundingClientRect();
                const x = (e.clientX - rect.left - canvas.ds.offset[0]) / canvas.ds.scale;
                const y = (e.clientY - rect.top - canvas.ds.offset[1]) / canvas.ds.scale;
                self._lastMousePos = [x, y];
                
                // Update cursor based on zone
                self.updateCursor(x, y);
            });
            
            // Handle clicks
            canvasElement.addEventListener('mousedown', function(e) {
                if (e.button !== 0) return; // Left click only
                
                const rect = canvasElement.getBoundingClientRect();
                const x = (e.clientX - rect.left - canvas.ds.offset[0]) / canvas.ds.scale;
                const y = (e.clientY - rect.top - canvas.ds.offset[1]) / canvas.ds.scale;
                
                // Find node at position
                const node = self.graph.getNodeOnPos(x, y, self.graph._nodes);
                
                if (node) {
                    const localX = x - node.pos[0];
                    const localY = y - node.pos[1];
                    const zone = self.getMouseZone(node, localX, localY);
                    
                    if (zone === 'close') {
                        e.stopPropagation();
                        e.preventDefault();
                        self.deleteNode(node);
                        return false;
                    }
                }
            }, true);
            
            // Handle double-click for form
            canvasElement.addEventListener('dblclick', function(e) {
                const rect = canvasElement.getBoundingClientRect();
                const x = (e.clientX - rect.left - canvas.ds.offset[0]) / canvas.ds.scale;
                const y = (e.clientY - rect.top - canvas.ds.offset[1]) / canvas.ds.scale;
                
                // Find node at position
                const node = self.graph.getNodeOnPos(x, y, self.graph._nodes);
                
                if (node) {
                    const localX = x - node.pos[0];
                    const localY = y - node.pos[1];
                    const zone = self.getMouseZone(node, localX, localY);
                    
                    // Open form on double-click (body area)
                    if (zone === 'body' || zone === 'header') {
                        e.stopPropagation();
                        e.preventDefault();
                        self.openNodeForm(node);
                    }
                }
            });
        },

        /**
         * Update cursor based on mouse position
         */
        updateCursor: function(x, y) {
            const canvas = this.graphCanvas;
            const canvasElement = canvas.canvas;
            
            // Find node at position
            const node = this.graph.getNodeOnPos(x, y, this.graph._nodes);
            
            if (node) {
                const localX = x - node.pos[0];
                const localY = y - node.pos[1];
                const zone = this.getMouseZone(node, localX, localY);
                
                switch (zone) {
                    case 'close':
                        canvasElement.style.cursor = 'pointer';
                        break;
                    case 'header':
                        canvasElement.style.cursor = 'grab';
                        break;
                    case 'connection':
                        canvasElement.style.cursor = 'crosshair';
                        break;
                    case 'body':
                        canvasElement.style.cursor = 'pointer';
                        break;
                    default:
                        canvasElement.style.cursor = 'default';
                }
            } else {
                canvasElement.style.cursor = 'default';
            }
        },

        /**
         * Delete a node
         */
        deleteNode: function(node) {
            const self = this;
            const nodeName = node.title || 'this node';
            
            if (global.VisualPrompter && global.VisualPrompter.showConfirm) {
                global.VisualPrompter.showConfirm({
                    title: 'Delete Node',
                    message: `Are you sure you want to delete "${nodeName}"?`,
                    confirmText: 'Delete',
                    cancelText: 'Cancel',
                    type: 'danger'
                }).then(confirmed => {
                    if (confirmed) {
                        self.graph.remove(node);
                        global.VisualPrompter.showToast('Node deleted', 'success');
                    }
                });
            }
        },

        /**
         * Open node form/popup
         */
        openNodeForm: function(node) {
            if (node.getPopupData && global.PopupEditor) {
                global.PopupEditor.open(node);
            }
        }
    };

    // Export to global
    global.NodeEnhancer = NodeEnhancer;

})(typeof window !== 'undefined' ? window : this);

