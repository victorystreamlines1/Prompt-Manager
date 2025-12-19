/**
 * Frontend Node Definition for Visual Prompter
 * Represents frontend application, pages, components
 */

(function(global) {
    'use strict';

    // Frontend Node Constructor
    function FrontendNode() {
        // Node Properties
        this.properties = {
            title: 'React App',
            framework: 'react',
            styling: 'tailwind',
            pages: [],
            components: [],
            description: ''
        };

        // Add input/output slots
        this.addInput('api', 'api');
        this.addInput('data', 'data');
        this.addOutput('ui', 'ui');

        // Node appearance
        this.size = [180, 80];
        this.color = '#8B5CF6';
        this.bgcolor = '#1A1A2E';
    }

    // Node Title
    FrontendNode.title = 'Frontend';
    FrontendNode.desc = 'Frontend application node';

    // Custom draw function
    FrontendNode.prototype.onDrawForeground = function(ctx) {
        if (this.flags.collapsed) return;

        ctx.font = '12px Outfit';
        ctx.fillStyle = '#A0A0B0';
        
        // Draw icon
        ctx.font = '18px Arial';
        ctx.fillText('▭', 10, 28);
        
        // Draw framework badge
        ctx.font = '10px Outfit';
        ctx.fillStyle = this.color;
        ctx.fillText(this.properties.framework.toUpperCase(), 35, 28);
        
        // Draw page count
        const pageCount = this.properties.pages.length;
        const compCount = this.properties.components.length;
        ctx.fillStyle = '#6B6B80';
        if (pageCount > 0) {
            ctx.fillText(`🖥️ ${pageCount} pages`, 10, 55);
        }
        if (compCount > 0) {
            ctx.fillText(`🧩 ${compCount}`, 90, 55);
        }
    };

    // Get data for popup editor
    FrontendNode.prototype.getPopupData = function() {
        return {
            type: 'frontend',
            icon: '▭',
            color: this.color,
            title: 'Frontend Application',
            fields: [
                { key: 'title', label: 'Title', type: 'text', placeholder: 'My Frontend App' },
                { 
                    key: 'framework', 
                    label: 'Framework', 
                    type: 'select',
                    options: [
                        { value: 'react', label: 'React' },
                        { value: 'vue', label: 'Vue.js' },
                        { value: 'angular', label: 'Angular' },
                        { value: 'svelte', label: 'Svelte' },
                        { value: 'nextjs', label: 'Next.js' },
                        { value: 'nuxt', label: 'Nuxt.js' },
                        { value: 'html', label: 'HTML/CSS/JS' }
                    ]
                },
                { 
                    key: 'styling', 
                    label: 'Styling', 
                    type: 'select',
                    options: [
                        { value: 'tailwind', label: 'Tailwind CSS' },
                        { value: 'css', label: 'Plain CSS' },
                        { value: 'scss', label: 'SCSS/SASS' },
                        { value: 'bootstrap', label: 'Bootstrap' },
                        { value: 'material', label: 'Material UI' },
                        { value: 'chakra', label: 'Chakra UI' },
                        { value: 'styled', label: 'Styled Components' }
                    ]
                },
                { key: 'pages', label: 'Pages', type: 'page-list' },
                { key: 'components', label: 'Components', type: 'component-list' },
                { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Describe the frontend features...' }
            ]
        };
    };

    // Serialize node data
    FrontendNode.prototype.onSerialize = function(o) {
        o.properties = JSON.parse(JSON.stringify(this.properties));
    };

    // Deserialize node data
    FrontendNode.prototype.onConfigure = function(o) {
        if (o.properties) {
            this.properties = JSON.parse(JSON.stringify(o.properties));
        }
    };

    // Get node data for prompt generation
    FrontendNode.prototype.getPromptData = function() {
        return {
            type: 'frontend',
            title: this.properties.title,
            framework: this.properties.framework,
            styling: this.properties.styling,
            pages: this.properties.pages,
            components: this.properties.components,
            description: this.properties.description
        };
    };

    // Register the node type
    LiteGraph.registerNodeType('VisualPrompter/Frontend', FrontendNode);
    
    // Export to global
    global.FrontendNode = FrontendNode;

})(typeof window !== 'undefined' ? window : this);

