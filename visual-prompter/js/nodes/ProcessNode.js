/**
 * Process/Function Node Definition for Visual Prompter
 * Represents a function, process, or operation
 */

(function(global) {
    'use strict';

    // Process Node Constructor
    function ProcessNode() {
        // Node Properties
        this.properties = {
            title: 'Calculate Total',
            functionName: 'calculateTotal',
            inputs: [],
            outputs: [],
            description: ''
        };

        // Add input/output slots
        this.addInput('trigger', 'trigger');
        this.addInput('data', 'data');
        this.addOutput('result', 'result');
        this.addOutput('next', 'next');

        // Node appearance
        this.size = [180, 80];
        this.color = '#10B981';
        this.bgcolor = '#1A1A2E';
    }

    // Node Title
    ProcessNode.title = 'Process';
    ProcessNode.desc = 'Process/Function node';

    // Custom draw function
    ProcessNode.prototype.onDrawForeground = function(ctx) {
        if (this.flags.collapsed) return;

        ctx.font = '12px Outfit';
        ctx.fillStyle = '#A0A0B0';
        
        // Draw icon
        ctx.font = '18px Arial';
        ctx.fillText('●', 10, 28);
        
        // Draw function name
        ctx.font = '10px JetBrains Mono, monospace';
        ctx.fillStyle = this.color;
        const funcName = this.properties.functionName.length > 18
            ? this.properties.functionName.substring(0, 16) + '...'
            : this.properties.functionName;
        ctx.fillText(funcName + '()', 35, 28);
        
        // Draw I/O counts
        const inputCount = this.properties.inputs.length;
        const outputCount = this.properties.outputs.length;
        ctx.fillStyle = '#6B6B80';
        ctx.fillText(`⬅ ${inputCount} in  ➡ ${outputCount} out`, 10, 55);
    };

    // Get data for popup editor
    ProcessNode.prototype.getPopupData = function() {
        return {
            type: 'process',
            icon: '●',
            color: this.color,
            title: 'Process / Function',
            fields: [
                { key: 'title', label: 'Title', type: 'text', placeholder: 'My Process' },
                { key: 'functionName', label: 'Function Name', type: 'text', placeholder: 'myFunction' },
                { key: 'inputs', label: 'Inputs', type: 'param-list', paramType: 'input' },
                { key: 'outputs', label: 'Outputs', type: 'param-list', paramType: 'output' },
                { key: 'description', label: 'Description / Logic', type: 'textarea', placeholder: 'Describe what this function does and its logic...' }
            ]
        };
    };

    // Serialize node data
    ProcessNode.prototype.onSerialize = function(o) {
        o.properties = JSON.parse(JSON.stringify(this.properties));
    };

    // Deserialize node data
    ProcessNode.prototype.onConfigure = function(o) {
        if (o.properties) {
            this.properties = JSON.parse(JSON.stringify(o.properties));
        }
    };

    // Get node data for prompt generation
    ProcessNode.prototype.getPromptData = function() {
        return {
            type: 'process',
            title: this.properties.title,
            functionName: this.properties.functionName,
            inputs: this.properties.inputs,
            outputs: this.properties.outputs,
            description: this.properties.description
        };
    };

    // Register the node type
    LiteGraph.registerNodeType('VisualPrompter/Process', ProcessNode);
    
    // Export to global
    global.ProcessNode = ProcessNode;

})(typeof window !== 'undefined' ? window : this);

