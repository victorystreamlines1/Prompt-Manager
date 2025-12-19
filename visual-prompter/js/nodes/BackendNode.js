/**
 * Backend Node Definition for Visual Prompter
 * Represents backend code, controllers, services
 */

(function(global) {
    'use strict';

    // Backend Node Constructor
    function BackendNode() {
        // Node Properties
        this.properties = {
            title: 'Laravel Backend',
            language: 'php',
            framework: 'laravel',
            files: [],
            description: ''
        };

        // Add input/output slots
        this.addInput('database', 'data');
        this.addInput('request', 'request');
        this.addOutput('response', 'response');
        this.addOutput('api', 'api');

        // Node appearance
        this.size = [180, 80];
        this.color = '#6366F1';
        this.bgcolor = '#1A1A2E';
    }

    // Node Title
    BackendNode.title = 'Backend';
    BackendNode.desc = 'Backend component node';

    // Framework options by language
    BackendNode.frameworks = {
        php: ['Laravel', 'Symfony', 'CodeIgniter', 'Slim', 'Plain PHP'],
        python: ['Django', 'Flask', 'FastAPI', 'Pyramid', 'Plain Python'],
        nodejs: ['Express', 'NestJS', 'Fastify', 'Koa', 'Hapi'],
        java: ['Spring Boot', 'Jakarta EE', 'Micronaut', 'Quarkus'],
        csharp: ['ASP.NET Core', 'ASP.NET MVC', '.NET Minimal API'],
        go: ['Gin', 'Echo', 'Fiber', 'Chi', 'Standard Library'],
        ruby: ['Rails', 'Sinatra', 'Hanami', 'Grape']
    };

    // Custom draw function
    BackendNode.prototype.onDrawForeground = function(ctx) {
        if (this.flags.collapsed) return;

        ctx.font = '12px Outfit';
        ctx.fillStyle = '#A0A0B0';
        
        // Draw icon
        ctx.font = '18px Arial';
        ctx.fillText('◼', 10, 28);
        
        // Draw framework badge
        ctx.font = '10px Outfit';
        ctx.fillStyle = this.color;
        ctx.fillText(this.properties.framework.toUpperCase(), 35, 28);
        
        // Draw file count
        const fileCount = this.properties.files.length;
        if (fileCount > 0) {
            ctx.fillStyle = '#6B6B80';
            ctx.fillText(`📄 ${fileCount} file${fileCount > 1 ? 's' : ''}`, 10, 55);
        }
    };

    // Get data for popup editor
    BackendNode.prototype.getPopupData = function() {
        return {
            type: 'backend',
            icon: '◼',
            color: this.color,
            title: 'Backend Component',
            fields: [
                { key: 'title', label: 'Title', type: 'text', placeholder: 'My Backend' },
                { 
                    key: 'language', 
                    label: 'Language', 
                    type: 'select',
                    options: [
                        { value: 'php', label: 'PHP' },
                        { value: 'python', label: 'Python' },
                        { value: 'nodejs', label: 'Node.js' },
                        { value: 'java', label: 'Java' },
                        { value: 'csharp', label: 'C#' },
                        { value: 'go', label: 'Go' },
                        { value: 'ruby', label: 'Ruby' }
                    ],
                    onChange: 'updateFrameworks'
                },
                { 
                    key: 'framework', 
                    label: 'Framework', 
                    type: 'select',
                    dynamicOptions: true,
                    dependsOn: 'language'
                },
                { key: 'files', label: 'Files / Controllers', type: 'file-list' },
                { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Describe the backend functionality...' }
            ]
        };
    };

    // Get framework options for selected language
    BackendNode.prototype.getFrameworkOptions = function() {
        const lang = this.properties.language;
        const frameworks = BackendNode.frameworks[lang] || ['Custom'];
        return frameworks.map(f => ({ value: f.toLowerCase().replace(/\s+/g, '_'), label: f }));
    };

    // Serialize node data
    BackendNode.prototype.onSerialize = function(o) {
        o.properties = JSON.parse(JSON.stringify(this.properties));
    };

    // Deserialize node data
    BackendNode.prototype.onConfigure = function(o) {
        if (o.properties) {
            this.properties = JSON.parse(JSON.stringify(o.properties));
        }
    };

    // Get node data for prompt generation
    BackendNode.prototype.getPromptData = function() {
        return {
            type: 'backend',
            title: this.properties.title,
            language: this.properties.language,
            framework: this.properties.framework,
            files: this.properties.files,
            description: this.properties.description
        };
    };

    // Register the node type
    LiteGraph.registerNodeType('VisualPrompter/Backend', BackendNode);
    
    // Export to global
    global.BackendNode = BackendNode;

})(typeof window !== 'undefined' ? window : this);

