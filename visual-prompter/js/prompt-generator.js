/**
 * Prompt Generator for Visual Prompter
 * Converts visual diagram to structured AI prompt
 */

(function(global) {
    'use strict';

    const PromptGenerator = {
        /**
         * Generate prompt from graph
         * @param {Object} graph - LiteGraph graph
         * @param {string} projectName - Project name
         * @returns {string} Generated prompt text
         */
        generate: function(graph, projectName) {
            if (!graph) return '';

            const nodes = graph._nodes || [];
            if (nodes.length === 0) {
                return '# Empty Project\n\nNo nodes have been added to the diagram yet.';
            }

            // Collect nodes by type
            const nodesByType = this.categorizeNodes(nodes);
            
            // Collect connections
            const connections = this.collectConnections(graph);

            // Generate prompt sections
            let prompt = '';
            
            // Header
            prompt += this.generateHeader(projectName, nodes.length, connections.length);
            
            // Overview
            prompt += this.generateOverview(nodesByType);
            
            // Tech Stack
            prompt += this.generateTechStack(nodesByType);
            
            // Database Section
            if (nodesByType.database.length > 0) {
                prompt += this.generateDatabaseSection(nodesByType.database);
            }
            
            // Backend Section
            if (nodesByType.backend.length > 0) {
                prompt += this.generateBackendSection(nodesByType.backend);
            }
            
            // Frontend Section
            if (nodesByType.frontend.length > 0) {
                prompt += this.generateFrontendSection(nodesByType.frontend);
            }
            
            // API Section
            if (nodesByType.api.length > 0) {
                prompt += this.generateAPISection(nodesByType.api);
            }
            
            // Process Section
            if (nodesByType.process.length > 0) {
                prompt += this.generateProcessSection(nodesByType.process);
            }
            
            // Decision Section
            if (nodesByType.decision.length > 0) {
                prompt += this.generateDecisionSection(nodesByType.decision);
            }
            
            // Service Section
            if (nodesByType.service.length > 0) {
                prompt += this.generateServiceSection(nodesByType.service);
            }
            
            // Data Flow
            if (connections.length > 0) {
                prompt += this.generateDataFlowSection(connections, nodes);
            }
            
            // Additional Requirements
            prompt += this.generateRequirements(nodesByType);
            
            return prompt;
        },

        /**
         * Categorize nodes by type
         */
        categorizeNodes: function(nodes) {
            const categories = {
                database: [],
                backend: [],
                frontend: [],
                api: [],
                process: [],
                decision: [],
                service: []
            };

            nodes.forEach(node => {
                const nodeType = this.getNodeType(node);
                if (categories[nodeType]) {
                    const data = node.getPromptData ? node.getPromptData() : node.properties;
                    categories[nodeType].push(data);
                }
            });

            return categories;
        },

        /**
         * Get node type from node
         */
        getNodeType: function(node) {
            if (node.type) {
                const parts = node.type.split('/');
                return parts[parts.length - 1].toLowerCase();
            }
            return 'unknown';
        },

        /**
         * Collect connections from graph
         */
        collectConnections: function(graph) {
            const connections = [];
            const links = graph.links || {};

            Object.values(links).forEach(link => {
                if (link) {
                    const sourceNode = graph.getNodeById(link.origin_id);
                    const targetNode = graph.getNodeById(link.target_id);
                    
                    if (sourceNode && targetNode) {
                        connections.push({
                            from: sourceNode.properties?.title || sourceNode.title || 'Node',
                            to: targetNode.properties?.title || targetNode.title || 'Node',
                            fromType: this.getNodeType(sourceNode),
                            toType: this.getNodeType(targetNode)
                        });
                    }
                }
            });

            return connections;
        },

        /**
         * Generate header section
         */
        generateHeader: function(projectName, nodeCount, connectionCount) {
            const date = new Date().toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            return `# Project: ${projectName || 'Untitled Project'}

> Generated by Visual Prompter on ${date}
> Components: ${nodeCount} | Connections: ${connectionCount}

---

`;
        },

        /**
         * Generate overview section
         */
        generateOverview: function(nodesByType) {
            const components = [];
            
            if (nodesByType.database.length > 0) components.push('database layer');
            if (nodesByType.backend.length > 0) components.push('backend services');
            if (nodesByType.frontend.length > 0) components.push('frontend application');
            if (nodesByType.api.length > 0) components.push('API endpoints');
            if (nodesByType.service.length > 0) components.push('external service integrations');
            
            return `## Overview

Build a complete application with the following components:
${components.map(c => `- ${c.charAt(0).toUpperCase() + c.slice(1)}`).join('\n')}

`;
        },

        /**
         * Generate tech stack section
         */
        generateTechStack: function(nodesByType) {
            let stack = '## Tech Stack\n\n';
            
            // Database
            if (nodesByType.database.length > 0) {
                const dbTypes = [...new Set(nodesByType.database.map(d => d.dbType))];
                stack += `- **Database**: ${dbTypes.map(t => t.charAt(0).toUpperCase() + t.slice(1)).join(', ')}\n`;
            }
            
            // Backend
            if (nodesByType.backend.length > 0) {
                const languages = [...new Set(nodesByType.backend.map(b => b.language))];
                const frameworks = [...new Set(nodesByType.backend.map(b => b.framework))];
                stack += `- **Backend**: ${languages.map(l => l.toUpperCase()).join(', ')} with ${frameworks.join(', ')}\n`;
            }
            
            // Frontend
            if (nodesByType.frontend.length > 0) {
                const fwks = [...new Set(nodesByType.frontend.map(f => f.framework))];
                const styles = [...new Set(nodesByType.frontend.map(f => f.styling))];
                stack += `- **Frontend**: ${fwks.map(f => f.charAt(0).toUpperCase() + f.slice(1)).join(', ')}\n`;
                stack += `- **Styling**: ${styles.map(s => s.charAt(0).toUpperCase() + s.slice(1)).join(', ')}\n`;
            }
            
            // Services
            if (nodesByType.service.length > 0) {
                const services = nodesByType.service.map(s => s.service);
                stack += `- **Integrations**: ${services.join(', ')}\n`;
            }
            
            return stack + '\n';
        },

        /**
         * Generate database section
         */
        generateDatabaseSection: function(databases) {
            let section = '## Database Structure\n\n';
            
            databases.forEach(db => {
                section += `### ${db.title || 'Database'}\n\n`;
                section += `- **Type**: ${db.dbType || 'Not specified'}\n`;
                if (db.host) section += `- **Host**: ${db.host}${db.port ? ':' + db.port : ''}\n`;
                if (db.database) section += `- **Database Name**: ${db.database}\n`;
                section += '\n';
                
                // Tables
                if (db.tables && db.tables.length > 0) {
                    section += '#### Tables\n\n';
                    db.tables.forEach(table => {
                        section += `##### ${table.name}\n`;
                        if (table.columns && table.columns.length > 0) {
                            table.columns.forEach(col => {
                                const primary = col.primary ? ' (Primary Key)' : '';
                                section += `- \`${col.name}\`: ${col.type}${primary}\n`;
                            });
                        }
                        section += '\n';
                    });
                }
                
                if (db.description) {
                    section += `*${db.description}*\n\n`;
                }
            });
            
            return section;
        },

        /**
         * Generate backend section
         */
        generateBackendSection: function(backends) {
            let section = '## Backend\n\n';
            
            backends.forEach(backend => {
                section += `### ${backend.title || 'Backend Component'}\n\n`;
                section += `- **Language**: ${backend.language || 'Not specified'}\n`;
                section += `- **Framework**: ${backend.framework || 'Not specified'}\n`;
                
                if (backend.files && backend.files.length > 0) {
                    section += '\n**Files/Controllers:**\n';
                    backend.files.forEach(file => {
                        section += `- \`${file}\`\n`;
                    });
                }
                
                if (backend.description) {
                    section += `\n*${backend.description}*\n`;
                }
                
                section += '\n';
            });
            
            return section;
        },

        /**
         * Generate frontend section
         */
        generateFrontendSection: function(frontends) {
            let section = '## Frontend\n\n';
            
            frontends.forEach(frontend => {
                section += `### ${frontend.title || 'Frontend Application'}\n\n`;
                section += `- **Framework**: ${frontend.framework || 'Not specified'}\n`;
                section += `- **Styling**: ${frontend.styling || 'CSS'}\n`;
                
                if (frontend.pages && frontend.pages.length > 0) {
                    section += '\n**Pages:**\n';
                    frontend.pages.forEach(page => {
                        const route = typeof page === 'object' ? page.route : '/';
                        const name = typeof page === 'object' ? page.name : page;
                        section += `- ${name} (\`${route}\`)\n`;
                    });
                }
                
                if (frontend.components && frontend.components.length > 0) {
                    section += '\n**Components:**\n';
                    frontend.components.forEach(comp => {
                        section += `- ${comp}\n`;
                    });
                }
                
                if (frontend.description) {
                    section += `\n*${frontend.description}*\n`;
                }
                
                section += '\n';
            });
            
            return section;
        },

        /**
         * Generate API section
         */
        generateAPISection: function(apis) {
            let section = '## API Endpoints\n\n';
            
            apis.forEach(api => {
                section += `### \`${api.method}\` ${api.endpoint}\n\n`;
                section += `**${api.title || 'API Endpoint'}**\n\n`;
                section += `- **Authentication**: ${api.auth || 'None'}\n`;
                
                if (api.requestBody && api.requestBody !== '{}') {
                    section += '\n**Request Body:**\n```json\n' + api.requestBody + '\n```\n';
                }
                
                if (api.responseBody && api.responseBody !== '{}') {
                    section += '\n**Response:**\n```json\n' + api.responseBody + '\n```\n';
                }
                
                if (api.description) {
                    section += `\n*${api.description}*\n`;
                }
                
                section += '\n';
            });
            
            return section;
        },

        /**
         * Generate process section
         */
        generateProcessSection: function(processes) {
            let section = '## Functions & Processes\n\n';
            
            processes.forEach(process => {
                section += `### ${process.title || 'Process'}\n\n`;
                section += `**Function**: \`${process.functionName || 'unnamed'}()\`\n\n`;
                
                if (process.inputs && process.inputs.length > 0) {
                    section += '**Inputs:**\n';
                    process.inputs.forEach(input => {
                        section += `- \`${input.name}\`: ${input.type}\n`;
                    });
                    section += '\n';
                }
                
                if (process.outputs && process.outputs.length > 0) {
                    section += '**Outputs:**\n';
                    process.outputs.forEach(output => {
                        section += `- \`${output.name}\`: ${output.type}\n`;
                    });
                    section += '\n';
                }
                
                if (process.description) {
                    section += `**Logic:**\n${process.description}\n`;
                }
                
                section += '\n';
            });
            
            return section;
        },

        /**
         * Generate decision section
         */
        generateDecisionSection: function(decisions) {
            let section = '## Decision Logic\n\n';
            
            decisions.forEach(decision => {
                section += `### ${decision.title || 'Decision'}\n\n`;
                section += `**Condition:** \`${decision.condition || 'undefined'}\`\n\n`;
                section += `- If **TRUE** → ${decision.trueLabel || 'Continue'}\n`;
                section += `- If **FALSE** → ${decision.falseLabel || 'Stop'}\n`;
                
                if (decision.description) {
                    section += `\n*${decision.description}*\n`;
                }
                
                section += '\n';
            });
            
            return section;
        },

        /**
         * Generate service section
         */
        generateServiceSection: function(services) {
            let section = '## External Services\n\n';
            
            services.forEach(service => {
                section += `### ${service.title || 'External Service'}\n\n`;
                section += `- **Provider**: ${service.service || 'Not specified'}\n`;
                if (service.apiBaseUrl) section += `- **API Base URL**: ${service.apiBaseUrl}\n`;
                section += `- **Environment**: ${service.environment || 'sandbox'}\n`;
                
                if (service.methods && service.methods.length > 0) {
                    section += '\n**Used Methods:**\n';
                    service.methods.forEach(method => {
                        section += `- \`${method}\`\n`;
                    });
                }
                
                if (service.description) {
                    section += `\n*${service.description}*\n`;
                }
                
                section += '\n';
            });
            
            return section;
        },

        /**
         * Generate data flow section
         */
        generateDataFlowSection: function(connections, nodes) {
            let section = '## Data Flow\n\n';
            section += 'The following describes how data flows between components:\n\n';
            
            connections.forEach(conn => {
                section += `- **${conn.from}** → **${conn.to}**\n`;
            });
            
            return section + '\n';
        },

        /**
         * Generate requirements section
         */
        generateRequirements: function(nodesByType) {
            let section = '## Additional Requirements\n\n';
            
            // Collect all descriptions
            const allNodes = [
                ...nodesByType.database,
                ...nodesByType.backend,
                ...nodesByType.frontend,
                ...nodesByType.api,
                ...nodesByType.process,
                ...nodesByType.decision,
                ...nodesByType.service
            ];
            
            const descriptions = allNodes
                .filter(n => n.description && n.description.trim())
                .map(n => `- ${n.description.trim()}`);
            
            if (descriptions.length > 0) {
                section += descriptions.join('\n') + '\n\n';
            } else {
                section += '- Follow best practices for code organization\n';
                section += '- Implement proper error handling\n';
                section += '- Add input validation where necessary\n\n';
            }
            
            section += '---\n\n';
            section += '*This prompt was generated from a visual diagram. Please implement the application following the structure and requirements described above.*\n';
            
            return section;
        }
    };

    // Export to global
    global.PromptGenerator = PromptGenerator;

})(typeof window !== 'undefined' ? window : this);

