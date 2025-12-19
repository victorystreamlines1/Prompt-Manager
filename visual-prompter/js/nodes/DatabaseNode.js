/**
 * Database Node Definition for Visual Prompter
 * Represents database systems (MySQL, PostgreSQL, MongoDB, etc.)
 */

(function(global) {
    'use strict';

    // Database Node Constructor
    function DatabaseNode() {
        // Node Properties
        this.properties = {
            title: 'MySQL Database',
            dbType: 'mysql',
            host: 'localhost',
            port: '3306',
            database: '',
            username: 'root',
            tables: [],
            description: ''
        };

        // Add input/output slots
        this.addOutput('data', 'data');
        this.addInput('query', 'query');

        // Node appearance
        this.size = [180, 80];
        this.color = '#F97316';
        this.bgcolor = '#1A1A2E';
    }

    // Node Title
    DatabaseNode.title = 'Database';
    DatabaseNode.desc = 'Database system node';

    // Custom draw function for the node
    DatabaseNode.prototype.onDrawForeground = function(ctx) {
        if (this.flags.collapsed) return;

        ctx.font = '12px Outfit';
        ctx.fillStyle = '#A0A0B0';
        
        // Draw icon
        ctx.font = '20px Arial';
        ctx.fillText('⬡', 10, 30);
        
        // Draw type badge
        ctx.font = '10px Outfit';
        ctx.fillStyle = this.color;
        ctx.fillText(this.properties.dbType.toUpperCase(), 40, 28);
        
        // Draw table count
        const tableCount = this.properties.tables.length;
        if (tableCount > 0) {
            ctx.fillStyle = '#6B6B80';
            ctx.fillText(`📊 ${tableCount} table${tableCount > 1 ? 's' : ''}`, 10, 55);
        }
    };

    // Get data for popup editor
    DatabaseNode.prototype.getPopupData = function() {
        return {
            type: 'database',
            icon: '⬡',
            color: this.color,
            title: 'Database',
            fields: [
                { key: 'title', label: 'Title', type: 'text', placeholder: 'My Database' },
                { 
                    key: 'dbType', 
                    label: 'Database Type', 
                    type: 'radio',
                    options: [
                        { value: 'mysql', label: 'MySQL' },
                        { value: 'postgresql', label: 'PostgreSQL' },
                        { value: 'mongodb', label: 'MongoDB' },
                        { value: 'sqlite', label: 'SQLite' },
                        { value: 'redis', label: 'Redis' },
                        { value: 'other', label: 'Other' }
                    ]
                },
                { key: 'host', label: 'Host', type: 'text', placeholder: 'localhost' },
                { key: 'port', label: 'Port', type: 'text', placeholder: '3306' },
                { key: 'database', label: 'Database Name', type: 'text', placeholder: 'my_database' },
                { key: 'username', label: 'Username', type: 'text', placeholder: 'root' },
                { key: 'tables', label: 'Tables', type: 'table-list' },
                { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Describe the purpose of this database...' }
            ]
        };
    };

    // Serialize node data
    DatabaseNode.prototype.onSerialize = function(o) {
        o.properties = JSON.parse(JSON.stringify(this.properties));
    };

    // Deserialize node data
    DatabaseNode.prototype.onConfigure = function(o) {
        if (o.properties) {
            this.properties = JSON.parse(JSON.stringify(o.properties));
        }
    };

    // Get node data for prompt generation
    DatabaseNode.prototype.getPromptData = function() {
        return {
            type: 'database',
            title: this.properties.title,
            dbType: this.properties.dbType,
            host: this.properties.host,
            port: this.properties.port,
            database: this.properties.database,
            tables: this.properties.tables,
            description: this.properties.description
        };
    };

    // Register the node type
    LiteGraph.registerNodeType('VisualPrompter/Database', DatabaseNode);
    
    // Export to global
    global.DatabaseNode = DatabaseNode;

})(typeof window !== 'undefined' ? window : this);

