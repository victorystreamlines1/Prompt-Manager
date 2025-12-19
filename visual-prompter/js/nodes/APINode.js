/**
 * API Endpoint Node Definition for Visual Prompter
 * Represents a single API endpoint
 */

(function(global) {
    'use strict';

    // API Node Constructor
    function APINode() {
        // Node Properties
        this.properties = {
            title: 'GET /users',
            method: 'GET',
            endpoint: '/api/users',
            auth: 'bearer',
            requestBody: '{}',
            responseBody: '{"success": true, "data": []}',
            description: ''
        };

        // Add input/output slots
        this.addInput('handler', 'handler');
        this.addOutput('response', 'response');

        // Node appearance
        this.size = [180, 80];
        this.color = '#06B6D4';
        this.bgcolor = '#1A1A2E';
    }

    // Node Title
    APINode.title = 'API';
    APINode.desc = 'API endpoint node';

    // Method colors
    APINode.methodColors = {
        GET: '#10B981',
        POST: '#6366F1',
        PUT: '#F59E0B',
        DELETE: '#EF4444',
        PATCH: '#8B5CF6'
    };

    // Custom draw function
    APINode.prototype.onDrawForeground = function(ctx) {
        if (this.flags.collapsed) return;

        ctx.font = '12px Outfit';
        
        // Draw icon
        ctx.font = '18px Arial';
        ctx.fillStyle = '#A0A0B0';
        ctx.fillText('▲', 10, 28);
        
        // Draw method badge
        const methodColor = APINode.methodColors[this.properties.method] || this.color;
        ctx.font = 'bold 10px Outfit';
        ctx.fillStyle = methodColor;
        ctx.fillText(this.properties.method, 35, 28);
        
        // Draw endpoint
        ctx.font = '10px JetBrains Mono, monospace';
        ctx.fillStyle = '#6B6B80';
        const endpoint = this.properties.endpoint.length > 20 
            ? this.properties.endpoint.substring(0, 18) + '...' 
            : this.properties.endpoint;
        ctx.fillText(endpoint, 10, 55);
    };

    // Get data for popup editor
    APINode.prototype.getPopupData = function() {
        return {
            type: 'api',
            icon: '▲',
            color: this.color,
            title: 'API Endpoint',
            fields: [
                { key: 'title', label: 'Title', type: 'text', placeholder: 'GET /users' },
                { 
                    key: 'method', 
                    label: 'HTTP Method', 
                    type: 'radio',
                    options: [
                        { value: 'GET', label: 'GET' },
                        { value: 'POST', label: 'POST' },
                        { value: 'PUT', label: 'PUT' },
                        { value: 'DELETE', label: 'DELETE' },
                        { value: 'PATCH', label: 'PATCH' }
                    ]
                },
                { key: 'endpoint', label: 'Endpoint', type: 'text', placeholder: '/api/users' },
                { 
                    key: 'auth', 
                    label: 'Authentication', 
                    type: 'select',
                    options: [
                        { value: 'none', label: 'None' },
                        { value: 'bearer', label: 'Bearer Token' },
                        { value: 'apikey', label: 'API Key' },
                        { value: 'session', label: 'Session' },
                        { value: 'oauth', label: 'OAuth 2.0' },
                        { value: 'basic', label: 'Basic Auth' }
                    ]
                },
                { key: 'requestBody', label: 'Request Body (JSON)', type: 'code', language: 'json' },
                { key: 'responseBody', label: 'Response Body (JSON)', type: 'code', language: 'json' },
                { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Describe what this endpoint does...' }
            ]
        };
    };

    // Serialize node data
    APINode.prototype.onSerialize = function(o) {
        o.properties = JSON.parse(JSON.stringify(this.properties));
    };

    // Deserialize node data
    APINode.prototype.onConfigure = function(o) {
        if (o.properties) {
            this.properties = JSON.parse(JSON.stringify(o.properties));
        }
    };

    // Get node data for prompt generation
    APINode.prototype.getPromptData = function() {
        return {
            type: 'api',
            title: this.properties.title,
            method: this.properties.method,
            endpoint: this.properties.endpoint,
            auth: this.properties.auth,
            requestBody: this.properties.requestBody,
            responseBody: this.properties.responseBody,
            description: this.properties.description
        };
    };

    // Register the node type
    LiteGraph.registerNodeType('VisualPrompter/API', APINode);
    
    // Export to global
    global.APINode = APINode;

})(typeof window !== 'undefined' ? window : this);

