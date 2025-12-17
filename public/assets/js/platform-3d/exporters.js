/**
 * ============================================
 * 3D Platform - Model Exporters
 * ============================================
 * 
 * PURPOSE:
 * Exports 3D models to various formats.
 * Supports: STL, OBJ, GLB, HTML Viewer
 * 
 * DEPENDENCIES:
 * - Three.js (loaded globally)
 * - GLTFExporter (optional)
 * 
 * EXPORTS:
 * - ModelExporter class
 * 
 * ============================================
 */

class ModelExporter {
    /**
     * Generate binary STL file.
     * 
     * @param {THREE.Vector3[]} vertices Array of Vector3 vertices
     * @param {string} name Model name for header
     * @returns {ArrayBuffer} Binary STL data
     */
    generateSTL(vertices, name = 'model') {
        const triangleCount = Math.floor(vertices.length / 3);
        console.log('📦 Generating binary STL:', triangleCount, 'triangles');

        // Binary STL: 80 header + 4 count + (50 * triangles)
        const bufferSize = 80 + 4 + (triangleCount * 50);
        const buffer = new ArrayBuffer(bufferSize);
        const dataView = new DataView(buffer);
        const uint8 = new Uint8Array(buffer);

        // Write header (80 bytes)
        const header = `Binary STL - ${name.substring(0, 40)}`;
        for (let i = 0; i < Math.min(header.length, 80); i++) {
            uint8[i] = header.charCodeAt(i);
        }

        // Write triangle count
        dataView.setUint32(80, triangleCount, true);

        // Write triangles
        let offset = 84;
        for (let i = 0; i < vertices.length; i += 3) {
            const v1 = vertices[i];
            const v2 = vertices[i + 1];
            const v3 = vertices[i + 2];

            if (!v1 || !v2 || !v3) continue;

            // Calculate normal
            const ax = v2.x - v1.x, ay = v2.y - v1.y, az = v2.z - v1.z;
            const bx = v3.x - v1.x, by = v3.y - v1.y, bz = v3.z - v1.z;
            const nx = ay * bz - az * by;
            const ny = az * bx - ax * bz;
            const nz = ax * by - ay * bx;
            const len = Math.sqrt(nx * nx + ny * ny + nz * nz) || 1;

            // Write normal
            dataView.setFloat32(offset, nx / len, true); offset += 4;
            dataView.setFloat32(offset, ny / len, true); offset += 4;
            dataView.setFloat32(offset, nz / len, true); offset += 4;

            // Write vertices
            dataView.setFloat32(offset, v1.x, true); offset += 4;
            dataView.setFloat32(offset, v1.y, true); offset += 4;
            dataView.setFloat32(offset, v1.z, true); offset += 4;
            dataView.setFloat32(offset, v2.x, true); offset += 4;
            dataView.setFloat32(offset, v2.y, true); offset += 4;
            dataView.setFloat32(offset, v2.z, true); offset += 4;
            dataView.setFloat32(offset, v3.x, true); offset += 4;
            dataView.setFloat32(offset, v3.y, true); offset += 4;
            dataView.setFloat32(offset, v3.z, true); offset += 4;

            // Attribute byte count
            dataView.setUint16(offset, 0, true); offset += 2;
        }

        console.log('📦 STL size:', (buffer.byteLength / 1024 / 1024).toFixed(2), 'MB');
        return buffer;
    }

    /**
     * Generate OBJ file content.
     * 
     * @param {THREE.Vector3[]} vertices Array of Vector3 vertices
     * @param {string} name Model name
     * @param {boolean} includeMTL Whether to reference MTL file
     * @returns {string} OBJ file content
     */
    generateOBJ(vertices, name = 'model', includeMTL = false) {
        let obj = '# OBJ file exported from Fusion Platform\n';
        obj += `# Object: ${name}\n\n`;

        if (includeMTL) {
            obj += `mtllib ${name}.mtl\n\n`;
        }

        // Write vertices
        for (const v of vertices) {
            obj += `v ${v.x.toFixed(6)} ${v.y.toFixed(6)} ${v.z.toFixed(6)}\n`;
        }

        obj += '\n';

        if (includeMTL) {
            obj += 'usemtl FusionMaterial\n';
        }

        obj += '# Faces\n';

        // Write faces (triangles, 1-based indexing)
        for (let i = 0; i < vertices.length; i += 3) {
            const idx = i + 1;
            obj += `f ${idx} ${idx + 1} ${idx + 2}\n`;
        }

        return obj;
    }

    /**
     * Generate MTL (material) file content.
     * 
     * @param {string} name Material name
     * @param {THREE.Color} color Material color
     * @param {number} metalness 0-1
     * @param {number} roughness 0-1
     * @returns {string} MTL file content
     */
    generateMTL(name, color, metalness = 0.3, roughness = 0.7) {
        const r = color.r.toFixed(6);
        const g = color.g.toFixed(6);
        const b = color.b.toFixed(6);
        const specular = Math.round((1 - roughness) * 200);

        let mtl = '# MTL file exported from Fusion Platform\n';
        mtl += `# Material for: ${name}\n\n`;
        mtl += 'newmtl FusionMaterial\n';
        mtl += `Ka ${(parseFloat(r) * 0.2).toFixed(6)} ${(parseFloat(g) * 0.2).toFixed(6)} ${(parseFloat(b) * 0.2).toFixed(6)}\n`;
        mtl += `Kd ${r} ${g} ${b}\n`;
        mtl += 'Ks 0.500000 0.500000 0.500000\n';
        mtl += `Ns ${specular}\n`;
        mtl += 'd 1.000000\n';
        mtl += 'illum 2\n';

        return mtl;
    }

    /**
     * Export mesh to GLB using Three.js GLTFExporter.
     * 
     * @param {THREE.Mesh} mesh Mesh to export
     * @param {string} filename Output filename
     * @returns {Promise<void>}
     */
    async exportGLB(mesh, filename) {
        if (!THREE.GLTFExporter) {
            throw new Error('GLTFExporter not available');
        }

        return new Promise((resolve, reject) => {
            const exporter = new THREE.GLTFExporter();
            const exportMesh = mesh.clone();

            // Clone material to preserve properties
            if (exportMesh.material && mesh.material) {
                exportMesh.material = mesh.material.clone();
            }

            exporter.parse(
                exportMesh,
                (result) => {
                    const blob = new Blob([result], { type: 'application/octet-stream' });
                    this.downloadBlob(blob, filename + '.glb');
                    resolve();
                },
                (error) => {
                    reject(error);
                },
                { binary: true }
            );
        });
    }

    /**
     * Generate standalone HTML viewer.
     * 
     * @param {object} geometryData Geometry data with vertices and settings
     * @param {string} title Page title
     * @param {object} options Material and scene options
     * @returns {string} Complete HTML file content
     */
    generateHTMLViewer(geometryData, title, options = {}) {
        const {
            color = '#6366f1',
            metalness = 0.3,
            roughness = 0.7,
            bgColor = '#1a1a2e'
        } = options;

        const geometryJson = JSON.stringify(geometryData);

        return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>${title} - Fusion 3D Viewer</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: ${bgColor}; overflow: hidden; }
#viewer { width: 100vw; height: 100vh; }
#loading { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #6366f1; font-family: system-ui, sans-serif; font-size: 18px; text-align: center; }
#loading .spinner { width: 40px; height: 40px; border: 3px solid #2a2a4a; border-top-color: #6366f1; border-radius: 50%; margin: 0 auto 15px; animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>
<div id="viewer"></div>
<div id="loading"><div class="spinner"></div><div id="status">Loading...</div></div>
<script type="application/json" id="geometry-data">${geometryJson}</script>
<script src="https://unpkg.com/three@0.160.0/build/three.min.js"></script>
<script>
(function() {
    var statusEl = document.getElementById('status');
    function updateStatus(msg) { statusEl.textContent = msg; }
    
    function init() {
        if (typeof THREE === 'undefined') {
            setTimeout(init, 50);
            return;
        }
        
        var container = document.getElementById('viewer');
        var scene = new THREE.Scene();
        scene.background = new THREE.Color('${bgColor}');
        
        var camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.01, 1000);
        camera.position.set(5, 5, 5);
        
        var renderer = new THREE.WebGLRenderer({ antialias: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        container.appendChild(renderer.domElement);
        
        scene.add(new THREE.AmbientLight(0xffffff, 0.6));
        var light = new THREE.DirectionalLight(0xffffff, 1);
        light.position.set(5, 10, 7);
        scene.add(light);
        
        updateStatus('Loading geometry...');
        
        var data = JSON.parse(document.getElementById('geometry-data').textContent);
        var vertices = new Float32Array(data.vertices);
        var geometry = new THREE.BufferGeometry();
        geometry.setAttribute('position', new THREE.BufferAttribute(vertices, 3));
        geometry.computeVertexNormals();
        
        var material = new THREE.MeshStandardMaterial({
            color: '${color}',
            metalness: ${metalness},
            roughness: ${roughness},
            side: THREE.DoubleSide
        });
        
        var mesh = new THREE.Mesh(geometry, material);
        geometry.computeBoundingBox();
        var center = geometry.boundingBox.getCenter(new THREE.Vector3());
        var size = geometry.boundingBox.getSize(new THREE.Vector3());
        var scale = 3 / Math.max(size.x, size.y, size.z);
        geometry.translate(-center.x, -center.y, -center.z);
        mesh.scale.setScalar(scale);
        scene.add(mesh);
        
        scene.add(new THREE.GridHelper(20, 40, 0x6366f1, 0x2a2a4a));
        
        document.getElementById('loading').style.display = 'none';
        
        // Simple orbit
        var isDragging = false, prevX = 0, prevY = 0;
        container.addEventListener('mousedown', function(e) { isDragging = true; prevX = e.clientX; prevY = e.clientY; });
        window.addEventListener('mouseup', function() { isDragging = false; });
        window.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            var dx = e.clientX - prevX, dy = e.clientY - prevY;
            camera.position.applyAxisAngle(new THREE.Vector3(0, 1, 0), -dx * 0.01);
            camera.lookAt(0, 0, 0);
            prevX = e.clientX; prevY = e.clientY;
        });
        container.addEventListener('wheel', function(e) {
            e.preventDefault();
            camera.position.multiplyScalar(e.deltaY > 0 ? 1.1 : 0.9);
        });
        
        function animate() {
            requestAnimationFrame(animate);
            renderer.render(scene, camera);
        }
        animate();
        
        window.addEventListener('resize', function() {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
    }
    init();
})();
</script>
</body>
</html>`;
    }

    /**
     * Download content as file.
     * 
     * @param {string|ArrayBuffer|Blob} content File content
     * @param {string} filename Download filename
     * @param {string} mimeType MIME type
     */
    download(content, filename, mimeType = 'application/octet-stream') {
        const blob = content instanceof Blob 
            ? content 
            : new Blob([content], { type: mimeType });
        
        this.downloadBlob(blob, filename);
    }

    /**
     * Download blob as file.
     * 
     * @param {Blob} blob
     * @param {string} filename
     */
    downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    /**
     * Get transformed vertices from mesh.
     * 
     * @param {THREE.Mesh} mesh
     * @returns {THREE.Vector3[]}
     */
    getTransformedVertices(mesh) {
        const vertices = [];
        
        if (!mesh || !mesh.geometry) {
            return vertices;
        }

        mesh.updateMatrixWorld(true);
        const positions = mesh.geometry.attributes.position;
        const matrix = mesh.matrixWorld;

        if (mesh.geometry.index) {
            const indices = mesh.geometry.index;
            for (let i = 0; i < indices.count; i++) {
                const idx = indices.getX(i);
                const vertex = new THREE.Vector3(
                    positions.getX(idx),
                    positions.getY(idx),
                    positions.getZ(idx)
                );
                vertex.applyMatrix4(matrix);
                vertices.push(vertex);
            }
        } else {
            for (let i = 0; i < positions.count; i++) {
                const vertex = new THREE.Vector3(
                    positions.getX(i),
                    positions.getY(i),
                    positions.getZ(i)
                );
                vertex.applyMatrix4(matrix);
                vertices.push(vertex);
            }
        }

        return vertices;
    }
}

// Export
window.ModelExporter = ModelExporter;

