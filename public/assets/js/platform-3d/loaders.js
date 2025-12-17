/**
 * ============================================
 * 3D Platform - File Loaders
 * ============================================
 * 
 * PURPOSE:
 * Parses various 3D file formats into vertex arrays.
 * Supports: STL (binary/ASCII), OBJ, FBX, GLB/GLTF
 * 
 * DEPENDENCIES:
 * - Three.js (loaded globally)
 * - FBXLoader (optional, loaded dynamically)
 * - GLTFLoader (optional, loaded dynamically)
 * 
 * EXPORTS:
 * - ModelLoader class
 * 
 * ============================================
 */

class ModelLoader {
    constructor() {
        this.fbxLoader = null;
        this.gltfLoader = null;
    }

    /**
     * Parse STL file (binary or ASCII).
     * 
     * @param {ArrayBuffer} buffer File buffer
     * @returns {Float32Array} Vertex array
     */
    parseSTL(buffer) {
        const vertices = [];

        // Detect binary vs ASCII
        const headerBytes = new Uint8Array(buffer, 0, Math.min(80, buffer.byteLength));
        const headerText = new TextDecoder().decode(headerBytes).trim().toLowerCase();
        const startsWithSolid = headerText.startsWith('solid');

        // Check if binary by file size matching expected binary format
        const dataView = new DataView(buffer);
        let isBinary = false;

        if (buffer.byteLength > 84) {
            const numTriangles = dataView.getUint32(80, true);
            const expectedSize = 84 + (numTriangles * 50);
            if (Math.abs(buffer.byteLength - expectedSize) < 100) {
                isBinary = true;
            }
        }

        if (!startsWithSolid || isBinary) {
            // Binary STL
            console.log('📄 Parsing Binary STL');
            try {
                const numTriangles = dataView.getUint32(80, true);
                console.log('📐 Triangles:', numTriangles);

                if (numTriangles > 0 && numTriangles < 50000000) {
                    let offset = 84;

                    for (let i = 0; i < numTriangles; i++) {
                        offset += 12; // Skip normal

                        for (let j = 0; j < 3; j++) {
                            const x = dataView.getFloat32(offset, true);
                            const y = dataView.getFloat32(offset + 4, true);
                            const z = dataView.getFloat32(offset + 8, true);
                            vertices.push(x, y, z);
                            offset += 12;
                        }

                        offset += 2; // Skip attribute
                    }
                }
            } catch (e) {
                console.error('Binary STL parse error:', e);
            }
        }

        // Try ASCII if binary failed
        if (vertices.length === 0) {
            console.log('📄 Parsing ASCII STL');
            try {
                const text = new TextDecoder().decode(buffer);
                const vertexRegex = /vertex\s+([-+]?[0-9]*\.?[0-9]+(?:[eE][-+]?[0-9]+)?)\s+([-+]?[0-9]*\.?[0-9]+(?:[eE][-+]?[0-9]+)?)\s+([-+]?[0-9]*\.?[0-9]+(?:[eE][-+]?[0-9]+)?)/gi;
                let match;
                
                while ((match = vertexRegex.exec(text)) !== null) {
                    vertices.push(
                        parseFloat(match[1]),
                        parseFloat(match[2]),
                        parseFloat(match[3])
                    );
                }
                console.log('📐 ASCII vertices:', vertices.length / 3);
            } catch (e) {
                console.error('ASCII STL parse error:', e);
            }
        }

        return new Float32Array(vertices);
    }

    /**
     * Parse OBJ file.
     * 
     * @param {string} text OBJ file content
     * @returns {Float32Array} Vertex array
     */
    parseOBJ(text) {
        const vertices = [];
        const positions = [];
        const lines = text.split('\n');

        // First pass: collect vertex positions
        for (const line of lines) {
            const parts = line.trim().split(/\s+/);
            if (parts[0] === 'v') {
                positions.push([
                    parseFloat(parts[1]) || 0,
                    parseFloat(parts[2]) || 0,
                    parseFloat(parts[3]) || 0
                ]);
            }
        }

        console.log('OBJ: Found', positions.length, 'vertex positions');

        // Second pass: process faces
        for (const line of lines) {
            const parts = line.trim().split(/\s+/);
            if (parts[0] === 'f') {
                const faceIndices = [];
                
                for (let i = 1; i < parts.length; i++) {
                    const idx = parseInt(parts[i].split('/')[0]) - 1;
                    if (idx >= 0 && idx < positions.length) {
                        faceIndices.push(idx);
                    }
                }

                // Triangulate face (fan triangulation)
                for (let i = 1; i < faceIndices.length - 1; i++) {
                    const v0 = positions[faceIndices[0]];
                    const v1 = positions[faceIndices[i]];
                    const v2 = positions[faceIndices[i + 1]];

                    if (v0 && v1 && v2) {
                        vertices.push(v0[0], v0[1], v0[2]);
                        vertices.push(v1[0], v1[1], v1[2]);
                        vertices.push(v2[0], v2[1], v2[2]);
                    }
                }
            }
        }

        console.log('OBJ: Generated', vertices.length / 3, 'vertices');
        return new Float32Array(vertices);
    }

    /**
     * Load FBX file using Three.js FBXLoader.
     * 
     * @param {ArrayBuffer} buffer File buffer
     * @returns {Promise<object>} Result with vertices and material info
     */
    async loadFBX(buffer) {
        // Wait for FBXLoader
        if (!window.FBXLoader) {
            await this.waitForLoader('FBXLoader', 5000);
        }

        if (!window.FBXLoader) {
            throw new Error('FBXLoader not available');
        }

        return new Promise((resolve, reject) => {
            const loader = new window.FBXLoader();
            const blob = new Blob([buffer]);
            const url = URL.createObjectURL(blob);

            loader.load(
                url,
                (fbxObject) => {
                    URL.revokeObjectURL(url);
                    
                    const result = this.extractFromThreeObject(fbxObject);
                    result.type = 'FBX';
                    resolve(result);
                },
                (progress) => {
                    if (progress.total > 0) {
                        console.log('FBX loading:', Math.round((progress.loaded / progress.total) * 100) + '%');
                    }
                },
                (error) => {
                    URL.revokeObjectURL(url);
                    reject(error);
                }
            );
        });
    }

    /**
     * Load GLB/GLTF file using Three.js GLTFLoader.
     * 
     * @param {ArrayBuffer} buffer File buffer
     * @returns {Promise<object>} Result with vertices and material info
     */
    async loadGLTF(buffer) {
        // Wait for GLTFLoader
        if (!window.GLTFLoader) {
            await this.waitForLoader('GLTFLoader', 5000);
        }

        if (!window.GLTFLoader) {
            throw new Error('GLTFLoader not available');
        }

        return new Promise((resolve, reject) => {
            const loader = new window.GLTFLoader();

            loader.parse(
                buffer,
                '',
                (gltf) => {
                    const result = this.extractFromThreeObject(gltf.scene);
                    result.type = 'GLTF';
                    resolve(result);
                },
                (error) => {
                    reject(error);
                }
            );
        });
    }

    /**
     * Extract vertices and material from Three.js object.
     * 
     * @param {THREE.Object3D} object
     * @returns {object} Extracted data
     */
    extractFromThreeObject(object) {
        const vertices = [];
        let color = null;
        let metalness = 0.3;
        let roughness = 0.7;

        object.traverse((child) => {
            if ((child.isMesh || child.isSkinnedMesh) && child.geometry) {
                const geometry = child.geometry;
                const positions = geometry.attributes.position;

                // Extract material properties from first mesh
                if (!color && child.material) {
                    const mat = Array.isArray(child.material) ? child.material[0] : child.material;
                    if (mat?.color) {
                        color = '#' + mat.color.getHexString();
                    }
                    if (mat?.metalness !== undefined) metalness = mat.metalness;
                    if (mat?.roughness !== undefined) roughness = mat.roughness;
                }

                if (positions) {
                    child.updateMatrixWorld(true);
                    const matrix = child.matrixWorld;

                    if (geometry.index) {
                        // Indexed geometry
                        const indices = geometry.index;
                        for (let i = 0; i < indices.count; i++) {
                            const idx = indices.getX(i);
                            const vertex = new THREE.Vector3(
                                positions.getX(idx),
                                positions.getY(idx),
                                positions.getZ(idx)
                            );
                            vertex.applyMatrix4(matrix);
                            vertices.push(vertex.x, vertex.y, vertex.z);
                        }
                    } else {
                        // Non-indexed
                        for (let i = 0; i < positions.count; i++) {
                            const vertex = new THREE.Vector3(
                                positions.getX(i),
                                positions.getY(i),
                                positions.getZ(i)
                            );
                            vertex.applyMatrix4(matrix);
                            vertices.push(vertex.x, vertex.y, vertex.z);
                        }
                    }
                }
            }
        });

        return {
            vertices: new Float32Array(vertices),
            color,
            metalness,
            roughness,
            originalObject: vertices.length === 0 ? object : null
        };
    }

    /**
     * Wait for a loader to become available.
     * 
     * @param {string} loaderName
     * @param {number} timeout
     * @returns {Promise<void>}
     */
    waitForLoader(loaderName, timeout = 5000) {
        return new Promise((resolve) => {
            const checkInterval = setInterval(() => {
                if (window[loaderName]) {
                    clearInterval(checkInterval);
                    resolve();
                }
            }, 100);

            setTimeout(() => {
                clearInterval(checkInterval);
                resolve();
            }, timeout);
        });
    }

    /**
     * Parse HTML file with embedded geometry.
     * 
     * @param {string} htmlContent HTML file content
     * @returns {object|null} Parsed geometry data
     */
    parseHTML(htmlContent) {
        // Try multiple patterns to find geometry data
        let geometryMatch = htmlContent.match(/id="geometry-data"[^>]*>(\{[^<]+\})</);
        if (!geometryMatch) {
            geometryMatch = htmlContent.match(/id='geometry-data'[^>]*>(\{[^<]+\})</);
        }

        if (geometryMatch) {
            try {
                return JSON.parse(geometryMatch[1]);
            } catch (e) {
                console.error('Failed to parse geometry JSON:', e);
            }
        }

        return null;
    }

    /**
     * Get file extension from filename.
     * 
     * @param {string} filename
     * @returns {string} Lowercase extension
     */
    getExtension(filename) {
        return filename.split('.').pop().toLowerCase();
    }

    /**
     * Check if file format is supported.
     * 
     * @param {string} filename
     * @returns {boolean}
     */
    isSupported(filename) {
        const supported = ['stl', 'obj', 'fbx', 'glb', 'gltf', 'html', 'htm'];
        return supported.includes(this.getExtension(filename));
    }
}

// Export
window.ModelLoader = ModelLoader;

