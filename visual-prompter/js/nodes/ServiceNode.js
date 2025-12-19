/**
 * External Service Node Definition for Visual Prompter
 * Represents third-party services (Stripe, AWS, Firebase, etc.)
 */

(function(global) {
    'use strict';

    // Service Node Constructor
    function ServiceNode() {
        // Node Properties
        this.properties = {
            title: 'Stripe Payment',
            service: 'stripe',
            apiBaseUrl: 'https://api.stripe.com/v1',
            apiKey: '',
            environment: 'sandbox',
            methods: [],
            description: ''
        };

        // Add input/output slots
        this.addInput('request', 'request');
        this.addOutput('response', 'response');
        this.addOutput('webhook', 'webhook');

        // Node appearance
        this.size = [180, 80];
        this.color = '#EC4899';
        this.bgcolor = '#1A1A2E';
    }

    // Node Title
    ServiceNode.title = 'Service';
    ServiceNode.desc = 'External service node';

    // Service options
    ServiceNode.services = [
        { value: 'stripe', label: 'Stripe', icon: '💳' },
        { value: 'paypal', label: 'PayPal', icon: '💰' },
        { value: 'aws', label: 'AWS', icon: '☁️' },
        { value: 'firebase', label: 'Firebase', icon: '🔥' },
        { value: 'twilio', label: 'Twilio', icon: '📱' },
        { value: 'sendgrid', label: 'SendGrid', icon: '📧' },
        { value: 'cloudinary', label: 'Cloudinary', icon: '🖼️' },
        { value: 'openai', label: 'OpenAI', icon: '🤖' },
        { value: 'google', label: 'Google APIs', icon: '🔍' },
        { value: 'other', label: 'Other', icon: '🔌' }
    ];

    // Custom draw function
    ServiceNode.prototype.onDrawForeground = function(ctx) {
        if (this.flags.collapsed) return;

        ctx.font = '12px Outfit';
        ctx.fillStyle = '#A0A0B0';
        
        // Draw icon
        ctx.font = '18px Arial';
        ctx.fillText('◎', 10, 28);
        
        // Get service info
        const serviceInfo = ServiceNode.services.find(s => s.value === this.properties.service) || { label: 'Service', icon: '🔌' };
        
        // Draw service name
        ctx.font = '10px Outfit';
        ctx.fillStyle = this.color;
        ctx.fillText(serviceInfo.icon + ' ' + serviceInfo.label.toUpperCase(), 35, 28);
        
        // Draw environment badge
        ctx.font = '9px Outfit';
        ctx.fillStyle = this.properties.environment === 'production' ? '#EF4444' : '#10B981';
        ctx.fillText(this.properties.environment.toUpperCase(), 10, 55);
        
        // Draw method count
        const methodCount = this.properties.methods.length;
        if (methodCount > 0) {
            ctx.fillStyle = '#6B6B80';
            ctx.fillText(`${methodCount} method${methodCount > 1 ? 's' : ''}`, 90, 55);
        }
    };

    // Get data for popup editor
    ServiceNode.prototype.getPopupData = function() {
        return {
            type: 'service',
            icon: '◎',
            color: this.color,
            title: 'External Service',
            fields: [
                { key: 'title', label: 'Title', type: 'text', placeholder: 'Payment Gateway' },
                { 
                    key: 'service', 
                    label: 'Service Provider', 
                    type: 'select',
                    options: ServiceNode.services
                },
                { key: 'apiBaseUrl', label: 'API Base URL', type: 'text', placeholder: 'https://api.example.com' },
                { key: 'apiKey', label: 'API Key (for reference)', type: 'password', placeholder: '••••••••••••••••' },
                { 
                    key: 'environment', 
                    label: 'Environment', 
                    type: 'radio',
                    options: [
                        { value: 'sandbox', label: 'Sandbox / Development' },
                        { value: 'production', label: 'Production' }
                    ]
                },
                { key: 'methods', label: 'Used Methods', type: 'method-list' },
                { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Describe how this service is used...' }
            ]
        };
    };

    // Serialize node data
    ServiceNode.prototype.onSerialize = function(o) {
        o.properties = JSON.parse(JSON.stringify(this.properties));
    };

    // Deserialize node data
    ServiceNode.prototype.onConfigure = function(o) {
        if (o.properties) {
            this.properties = JSON.parse(JSON.stringify(o.properties));
        }
    };

    // Get node data for prompt generation
    ServiceNode.prototype.getPromptData = function() {
        const serviceInfo = ServiceNode.services.find(s => s.value === this.properties.service) || { label: this.properties.service };
        return {
            type: 'service',
            title: this.properties.title,
            service: serviceInfo.label,
            serviceKey: this.properties.service,
            apiBaseUrl: this.properties.apiBaseUrl,
            environment: this.properties.environment,
            methods: this.properties.methods,
            description: this.properties.description
        };
    };

    // Register the node type
    LiteGraph.registerNodeType('VisualPrompter/Service', ServiceNode);
    
    // Export to global
    global.ServiceNode = ServiceNode;

})(typeof window !== 'undefined' ? window : this);

