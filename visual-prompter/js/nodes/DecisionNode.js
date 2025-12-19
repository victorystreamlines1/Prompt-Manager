/**
 * Decision/Condition Node Definition for Visual Prompter
 * Represents a conditional branch (if/else)
 */

(function(global) {
    'use strict';

    // Decision Node Constructor
    function DecisionNode() {
        // Node Properties
        this.properties = {
            title: 'Is Authenticated?',
            condition: 'user.isAuthenticated === true',
            trueLabel: 'Yes',
            falseLabel: 'No',
            description: ''
        };

        // Add input/output slots
        this.addInput('check', 'check');
        this.addOutput('true', 'true');
        this.addOutput('false', 'false');

        // Node appearance
        this.size = [180, 90];
        this.color = '#F59E0B';
        this.bgcolor = '#1A1A2E';
    }

    // Node Title
    DecisionNode.title = 'Decision';
    DecisionNode.desc = 'Conditional decision node';

    // Custom draw function
    DecisionNode.prototype.onDrawForeground = function(ctx) {
        if (this.flags.collapsed) return;

        ctx.font = '12px Outfit';
        ctx.fillStyle = '#A0A0B0';
        
        // Draw diamond icon
        ctx.font = '18px Arial';
        ctx.fillText('◆', 10, 28);
        
        // Draw condition preview
        ctx.font = '10px JetBrains Mono, monospace';
        ctx.fillStyle = this.color;
        const condPreview = this.properties.condition.length > 20
            ? this.properties.condition.substring(0, 18) + '...'
            : this.properties.condition;
        ctx.fillText(condPreview, 35, 28);
        
        // Draw true/false labels
        ctx.fillStyle = '#10B981';
        ctx.fillText(`✓ ${this.properties.trueLabel}`, 10, 55);
        ctx.fillStyle = '#EF4444';
        ctx.fillText(`✗ ${this.properties.falseLabel}`, 90, 55);
    };

    // Get data for popup editor
    DecisionNode.prototype.getPopupData = function() {
        return {
            type: 'decision',
            icon: '◆',
            color: this.color,
            title: 'Decision / Condition',
            fields: [
                { key: 'title', label: 'Title', type: 'text', placeholder: 'Is Valid?' },
                { key: 'condition', label: 'Condition (What to check)', type: 'code', language: 'javascript', placeholder: 'user.isAuthenticated === true' },
                { 
                    key: 'trueLabel', 
                    label: 'If TRUE → Label', 
                    type: 'text', 
                    placeholder: 'Yes / Authenticated / Valid',
                    color: '#10B981'
                },
                { 
                    key: 'falseLabel', 
                    label: 'If FALSE → Label', 
                    type: 'text', 
                    placeholder: 'No / Not Authenticated / Invalid',
                    color: '#EF4444'
                },
                { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Describe what this decision checks...' }
            ]
        };
    };

    // Serialize node data
    DecisionNode.prototype.onSerialize = function(o) {
        o.properties = JSON.parse(JSON.stringify(this.properties));
    };

    // Deserialize node data
    DecisionNode.prototype.onConfigure = function(o) {
        if (o.properties) {
            this.properties = JSON.parse(JSON.stringify(o.properties));
        }
    };

    // Get node data for prompt generation
    DecisionNode.prototype.getPromptData = function() {
        return {
            type: 'decision',
            title: this.properties.title,
            condition: this.properties.condition,
            trueLabel: this.properties.trueLabel,
            falseLabel: this.properties.falseLabel,
            description: this.properties.description
        };
    };

    // Register the node type
    LiteGraph.registerNodeType('VisualPrompter/Decision', DecisionNode);
    
    // Export to global
    global.DecisionNode = DecisionNode;

})(typeof window !== 'undefined' ? window : this);

