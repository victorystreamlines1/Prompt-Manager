/**
 * ============================================
 * 3D Platform - Scene Management
 * ============================================
 * 
 * PURPOSE:
 * Manages Three.js scene setup, camera, renderer,
 * lighting, and grid.
 * 
 * DEPENDENCIES:
 * - Three.js (loaded globally)
 * - OrbitControls (defined in controls.js)
 * 
 * EXPORTS:
 * - SceneManager class
 * 
 * ============================================
 */

class SceneManager {
    constructor(containerId = 'viewer-canvas') {
        this.container = document.getElementById(containerId);
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.gridHelper = null;
        this.lights = {};
        
        this.isInitialized = false;
    }

    /**
     * Initialize the 3D scene.
     * 
     * @param {object} options Configuration options
     * @returns {boolean} Success status
     */
    init(options = {}) {
        if (!this.container) {
            console.error('Scene container not found');
            return false;
        }

        if (typeof THREE === 'undefined') {
            console.error('Three.js not loaded');
            return false;
        }

        const {
            backgroundColor = '#1a1a2e',
            fov = 45,
            near = 0.01,
            far = 1000,
            gridSize = 20,
            gridDivisions = 40
        } = options;

        // Get container dimensions
        const width = this.container.clientWidth || window.innerWidth * 0.6;
        const height = this.container.clientHeight || window.innerHeight - 100;

        // Create scene
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(backgroundColor);

        // Create camera
        this.camera = new THREE.PerspectiveCamera(fov, width / height, near, far);
        this.camera.position.set(5, 5, 5);

        // Create renderer
        this.renderer = new THREE.WebGLRenderer({ antialias: true });
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.container.appendChild(this.renderer.domElement);

        // Setup OrbitControls
        if (typeof OrbitControls !== 'undefined') {
            this.controls = new OrbitControls(this.camera, this.renderer.domElement);
            this.controls.enableDamping = true;
            this.controls.dampingFactor = 0.05;
        }

        // Setup lights
        this.setupLights();

        // Setup grid
        this.setupGrid(gridSize, gridDivisions);

        // Handle resize
        window.addEventListener('resize', () => this.onResize());

        this.isInitialized = true;
        console.log('✓ Scene initialized');
        return true;
    }

    /**
     * Setup scene lighting.
     */
    setupLights() {
        // Ambient light
        this.lights.ambient = new THREE.AmbientLight(0xffffff, 0.6);
        this.scene.add(this.lights.ambient);

        // Main directional light
        this.lights.main = new THREE.DirectionalLight(0xffffff, 1);
        this.lights.main.position.set(5, 10, 7);
        this.scene.add(this.lights.main);

        // Fill light (accent color)
        this.lights.fill = new THREE.DirectionalLight(0x6366f1, 0.3);
        this.lights.fill.position.set(-5, 5, -5);
        this.scene.add(this.lights.fill);
    }

    /**
     * Setup grid helper.
     * 
     * @param {number} size Grid size
     * @param {number} divisions Number of divisions
     */
    setupGrid(size = 20, divisions = 40) {
        this.gridHelper = new THREE.GridHelper(size, divisions, 0x6366f1, 0x2a2a4a);
        this.gridHelper.material.opacity = 0.3;
        this.gridHelper.material.transparent = true;
        this.scene.add(this.gridHelper);
    }

    /**
     * Handle window resize.
     */
    onResize() {
        if (!this.container || !this.camera || !this.renderer) return;

        const width = this.container.clientWidth;
        const height = this.container.clientHeight;

        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(width, height);
    }

    /**
     * Animation loop tick.
     */
    update() {
        if (this.controls) {
            this.controls.update();
        }
    }

    /**
     * Render the scene.
     */
    render() {
        if (this.renderer && this.scene && this.camera) {
            this.renderer.render(this.scene, this.camera);
        }
    }

    /**
     * Set background color.
     * 
     * @param {string} color Hex color
     */
    setBackgroundColor(color) {
        if (this.scene) {
            this.scene.background = new THREE.Color(color);
        }
    }

    /**
     * Set grid opacity.
     * 
     * @param {number} opacity 0-1
     */
    setGridOpacity(opacity) {
        if (this.gridHelper) {
            this.gridHelper.material.opacity = opacity;
        }
    }

    /**
     * Toggle grid visibility.
     * 
     * @param {boolean} visible
     */
    setGridVisible(visible) {
        if (this.gridHelper) {
            this.gridHelper.visible = visible;
        }
    }

    /**
     * Reset camera to default position.
     */
    resetCamera() {
        if (this.camera && this.controls) {
            this.camera.position.set(5, 5, 5);
            this.controls.target.set(0, 0, 0);
            this.controls.update();
        }
    }

    /**
     * Set camera to preset view.
     * 
     * @param {string} view front, back, left, right, top, perspective
     */
    setCameraView(view) {
        const dist = 8;
        const positions = {
            front: [0, 0, dist],
            back: [0, 0, -dist],
            left: [-dist, 0, 0],
            right: [dist, 0, 0],
            top: [0, dist, 0.01],
            perspective: [dist / 2, dist / 2, dist / 2]
        };

        if (positions[view] && this.camera && this.controls) {
            this.camera.position.set(...positions[view]);
            this.controls.target.set(0, 0, 0);
            this.controls.update();
        }
    }

    /**
     * Zoom to fit object.
     * 
     * @param {THREE.Object3D} object Object to fit
     */
    zoomToFit(object) {
        if (!object || !this.camera || !this.controls) return;

        const box = new THREE.Box3().setFromObject(object);
        const size = box.getSize(new THREE.Vector3());
        const maxDim = Math.max(size.x, size.y, size.z);

        this.camera.position.set(maxDim * 2, maxDim * 2, maxDim * 2);
        this.controls.update();
    }

    /**
     * Add object to scene.
     * 
     * @param {THREE.Object3D} object
     */
    add(object) {
        if (this.scene) {
            this.scene.add(object);
        }
    }

    /**
     * Remove object from scene.
     * 
     * @param {THREE.Object3D} object
     */
    remove(object) {
        if (this.scene) {
            this.scene.remove(object);
        }
    }

    /**
     * Get canvas element.
     * 
     * @returns {HTMLCanvasElement}
     */
    getCanvas() {
        return this.renderer?.domElement;
    }

    /**
     * Dispose of scene resources.
     */
    dispose() {
        // Remove event listeners
        window.removeEventListener('resize', this.onResize);

        // Dispose of renderer
        if (this.renderer) {
            this.renderer.dispose();
        }

        // Clear scene
        if (this.scene) {
            this.scene.clear();
        }

        this.isInitialized = false;
    }
}

// Export
window.SceneManager = SceneManager;

