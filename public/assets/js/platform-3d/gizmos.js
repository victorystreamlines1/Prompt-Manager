/**
 * ============================================
 * 3D Platform - Transform Gizmos
 * ============================================
 * 
 * PURPOSE:
 * Provides interactive gizmos for scaling and
 * rotating 3D objects (like Fusion 360).
 * 
 * DEPENDENCIES:
 * - Three.js (loaded globally)
 * 
 * EXPORTS:
 * - ScaleGizmo class
 * - RotateGizmo class
 * 
 * ============================================
 */

// ============================================
// SCALE GIZMO
// ============================================

class ScaleGizmo {
    constructor(scene, camera, renderer) {
        this.scene = scene;
        this.camera = camera;
        this.renderer = renderer;
        this.canvas = renderer.domElement;
        
        this.group = null;
        this.handles = {};
        this.isActive = false;
        this.activeHandle = null;
        this.startScale = new THREE.Vector3();
        
        this.raycaster = new THREE.Raycaster();
        this.mouse = new THREE.Vector2();
        
        this.onScaleChange = null; // Callback
        
        this._create();
        this._setupInteraction();
    }

    /**
     * Create the scale gizmo geometry.
     */
    _create() {
        this.group = new THREE.Group();
        this.group.name = 'scaleGizmo';

        const arrowLength = 1.5;
        const coneRadius = 0.08;
        const coneHeight = 0.25;

        const colors = {
            x: 0xef4444, // Red
            y: 0x22c55e, // Green
            z: 0x3b82f6, // Blue
            uniform: 0xf59e0b // Yellow
        };

        // Create arrow for each axis
        ['x', 'y', 'z'].forEach(axis => {
            this.handles[axis] = this._createArrow(axis, colors[axis], arrowLength, coneRadius, coneHeight);
            this.group.add(this.handles[axis]);
        });

        // Create center cube for uniform scaling
        this.handles.uniform = this._createCenterCube(colors.uniform);
        this.group.add(this.handles.uniform);

        this.group.visible = false;
        this.scene.add(this.group);
    }

    _createArrow(axis, color, length, coneRadius, coneHeight) {
        const group = new THREE.Group();
        group.name = 'handle_' + axis;

        // Line
        const lineGeom = new THREE.BufferGeometry();
        const positions = {
            x: [0, 0, 0, length, 0, 0],
            y: [0, 0, 0, 0, length, 0],
            z: [0, 0, 0, 0, 0, length]
        };
        lineGeom.setAttribute('position', new THREE.Float32BufferAttribute(positions[axis], 3));
        const lineMat = new THREE.LineBasicMaterial({ color, linewidth: 3 });
        group.add(new THREE.Line(lineGeom, lineMat));

        // Cone
        const coneGeom = new THREE.ConeGeometry(coneRadius, coneHeight, 12);
        const coneMat = new THREE.MeshBasicMaterial({ color });
        const cone = new THREE.Mesh(coneGeom, coneMat);

        if (axis === 'x') {
            cone.rotation.z = -Math.PI / 2;
            cone.position.set(length, 0, 0);
        } else if (axis === 'y') {
            cone.position.set(0, length, 0);
        } else {
            cone.rotation.x = Math.PI / 2;
            cone.position.set(0, 0, length);
        }
        group.add(cone);

        // Hitbox
        const hitBoxGeom = new THREE.CylinderGeometry(0.12, 0.12, length, 8);
        const hitBoxMat = new THREE.MeshBasicMaterial({ visible: false });
        const hitBox = new THREE.Mesh(hitBoxGeom, hitBoxMat);
        hitBox.name = 'hitbox_' + axis;
        hitBox.userData.axis = axis;

        if (axis === 'x') {
            hitBox.rotation.z = -Math.PI / 2;
            hitBox.position.set(length / 2, 0, 0);
        } else if (axis === 'y') {
            hitBox.position.set(0, length / 2, 0);
        } else {
            hitBox.rotation.x = Math.PI / 2;
            hitBox.position.set(0, 0, length / 2);
        }
        group.add(hitBox);

        return group;
    }

    _createCenterCube(color) {
        const size = 0.2;
        const geom = new THREE.BoxGeometry(size, size, size);
        const mat = new THREE.MeshBasicMaterial({ color });
        const cube = new THREE.Mesh(geom, mat);
        cube.name = 'hitbox_uniform';
        cube.userData.axis = 'uniform';
        return cube;
    }

    /**
     * Setup mouse interaction.
     */
    _setupInteraction() {
        let isDragging = false;
        let dragAxis = null;
        let startMouseX = 0;
        let startMouseY = 0;

        const getMousePos = (e) => {
            const rect = this.canvas.getBoundingClientRect();
            return {
                x: e.clientX,
                y: e.clientY,
                normX: ((e.clientX - rect.left) / rect.width) * 2 - 1,
                normY: -((e.clientY - rect.top) / rect.height) * 2 + 1
            };
        };

        const checkHit = (normX, normY) => {
            this.mouse.set(normX, normY);
            this.raycaster.setFromCamera(this.mouse, this.camera);

            const hitboxes = [];
            this.group.traverse(obj => {
                if (obj.name?.startsWith('hitbox_')) {
                    hitboxes.push(obj);
                }
            });

            const intersects = this.raycaster.intersectObjects(hitboxes);
            return intersects.length > 0 ? intersects[0].object.userData.axis : null;
        };

        const onMouseDown = (e) => {
            if (!this.isActive || e.button !== 0) return;

            const pos = getMousePos(e);
            const axis = checkHit(pos.normX, pos.normY);

            if (axis && this.targetMesh) {
                isDragging = true;
                dragAxis = axis;
                this.activeHandle = axis;
                startMouseX = pos.x;
                startMouseY = pos.y;
                this.startScale.copy(this.targetMesh.scale);
                
                // Disable orbit controls
                if (this.controls) this.controls.enabled = false;
                
                e.preventDefault();
                e.stopPropagation();
            }
        };

        const onMouseMove = (e) => {
            if (!this.isActive) return;

            const pos = getMousePos(e);

            if (isDragging && dragAxis && this.targetMesh) {
                const deltaX = (pos.x - startMouseX) * 0.01;
                const deltaY = -(pos.y - startMouseY) * 0.01;
                const delta = dragAxis === 'y' ? deltaY : deltaX;

                let newScale;
                if (dragAxis === 'uniform') {
                    newScale = Math.max(0.01, this.startScale.x + delta);
                    this.targetMesh.scale.setScalar(newScale);
                } else {
                    newScale = Math.max(0.01, this.startScale[dragAxis] + delta);
                    this.targetMesh.scale[dragAxis] = newScale;
                }

                if (this.onScaleChange) {
                    this.onScaleChange(dragAxis, newScale, pos.x, pos.y);
                }
            } else {
                const axis = checkHit(pos.normX, pos.normY);
                this.canvas.style.cursor = axis ? 'pointer' : 'default';
            }
        };

        const onMouseUp = () => {
            if (isDragging) {
                isDragging = false;
                dragAxis = null;
                this.activeHandle = null;
                if (this.controls) this.controls.enabled = true;
            }
        };

        this.canvas.addEventListener('mousedown', onMouseDown);
        this.canvas.addEventListener('mousemove', onMouseMove);
        this.canvas.addEventListener('mouseup', onMouseUp);
        window.addEventListener('mouseup', onMouseUp);
    }

    /**
     * Show gizmo at mesh position.
     * 
     * @param {THREE.Mesh} mesh Target mesh
     * @param {OrbitControls} controls Orbit controls reference
     */
    show(mesh, controls) {
        this.targetMesh = mesh;
        this.controls = controls;
        
        if (mesh) {
            this.group.position.copy(mesh.position);
            this.group.visible = true;
            this.isActive = true;
        }
    }

    /**
     * Hide gizmo.
     */
    hide() {
        this.group.visible = false;
        this.isActive = false;
        this.targetMesh = null;
    }

    /**
     * Update gizmo position to follow mesh.
     */
    update() {
        if (this.targetMesh && this.group.visible) {
            this.group.position.copy(this.targetMesh.position);
        }
    }
}

// ============================================
// ROTATE GIZMO
// ============================================

class RotateGizmo {
    constructor(scene, camera, renderer) {
        this.scene = scene;
        this.camera = camera;
        this.renderer = renderer;
        this.canvas = renderer.domElement;
        
        this.group = null;
        this.rings = {};
        this.isActive = false;
        this.activeRing = null;
        this.startRotation = new THREE.Euler();
        
        this.raycaster = new THREE.Raycaster();
        this.mouse = new THREE.Vector2();
        
        this.onRotateChange = null; // Callback
        
        this._create();
        this._setupInteraction();
    }

    /**
     * Create the rotation gizmo geometry.
     */
    _create() {
        this.group = new THREE.Group();
        this.group.name = 'rotateGizmo';

        const ringRadius = 1.8;
        const tubeRadius = 0.03;
        const segments = 64;

        const colors = {
            x: 0xef4444,
            y: 0x22c55e,
            z: 0x3b82f6
        };

        ['x', 'y', 'z'].forEach(axis => {
            this.rings[axis] = this._createRing(axis, colors[axis], ringRadius, tubeRadius, segments);
            this.group.add(this.rings[axis]);
        });

        this.group.visible = false;
        this.scene.add(this.group);
    }

    _createRing(axis, color, radius, tube, segments) {
        const group = new THREE.Group();
        group.name = 'ring_' + axis;

        // Visible ring
        const torusGeom = new THREE.TorusGeometry(radius, tube, 16, segments);
        const torusMat = new THREE.MeshBasicMaterial({ color, transparent: true, opacity: 0.8 });
        const torus = new THREE.Mesh(torusGeom, torusMat);

        // Hitbox ring
        const hitGeom = new THREE.TorusGeometry(radius, 0.15, 8, segments);
        const hitMat = new THREE.MeshBasicMaterial({ visible: false });
        const hitRing = new THREE.Mesh(hitGeom, hitMat);
        hitRing.name = 'hitring_' + axis;
        hitRing.userData.axis = axis;

        // Rotate to correct orientation
        if (axis === 'x') {
            torus.rotation.y = Math.PI / 2;
            hitRing.rotation.y = Math.PI / 2;
        } else if (axis === 'y') {
            torus.rotation.x = Math.PI / 2;
            hitRing.rotation.x = Math.PI / 2;
        }

        group.add(torus);
        group.add(hitRing);

        return group;
    }

    /**
     * Setup mouse interaction.
     */
    _setupInteraction() {
        let isDragging = false;
        let dragAxis = null;
        let startMouseX = 0;
        let startMouseY = 0;
        let startAngle = 0;

        const getMousePos = (e) => {
            const rect = this.canvas.getBoundingClientRect();
            return {
                x: e.clientX,
                y: e.clientY,
                normX: ((e.clientX - rect.left) / rect.width) * 2 - 1,
                normY: -((e.clientY - rect.top) / rect.height) * 2 + 1
            };
        };

        const checkHit = (normX, normY) => {
            this.mouse.set(normX, normY);
            this.raycaster.setFromCamera(this.mouse, this.camera);

            const hitRings = [];
            this.group.traverse(obj => {
                if (obj.name?.startsWith('hitring_')) {
                    hitRings.push(obj);
                }
            });

            const intersects = this.raycaster.intersectObjects(hitRings);
            return intersects.length > 0 ? intersects[0].object.userData.axis : null;
        };

        const radToDeg = (rad) => rad * (180 / Math.PI);
        const degToRad = (deg) => deg * (Math.PI / 180);

        const onMouseDown = (e) => {
            if (!this.isActive || e.button !== 0) return;

            const pos = getMousePos(e);
            const axis = checkHit(pos.normX, pos.normY);

            if (axis && this.targetMesh) {
                isDragging = true;
                dragAxis = axis;
                this.activeRing = axis;
                startMouseX = pos.x;
                startMouseY = pos.y;
                this.startRotation.copy(this.targetMesh.rotation);
                startAngle = radToDeg(this.targetMesh.rotation[axis]);
                
                if (this.controls) this.controls.enabled = false;
                
                e.preventDefault();
                e.stopPropagation();
            }
        };

        const onMouseMove = (e) => {
            if (!this.isActive) return;

            const pos = getMousePos(e);

            if (isDragging && dragAxis && this.targetMesh) {
                const deltaX = pos.x - startMouseX;
                const deltaY = pos.y - startMouseY;

                let delta;
                if (dragAxis === 'y') {
                    delta = deltaX * 0.5;
                } else if (dragAxis === 'x') {
                    delta = deltaY * 0.5;
                } else {
                    delta = deltaX * 0.5;
                }

                const newAngle = startAngle + delta;
                this.targetMesh.rotation[dragAxis] = degToRad(newAngle);

                if (this.onRotateChange) {
                    this.onRotateChange(dragAxis, newAngle, pos.x, pos.y);
                }
            } else {
                const axis = checkHit(pos.normX, pos.normY);
                this.canvas.style.cursor = axis ? 'grab' : 'default';

                // Highlight hovered ring
                Object.keys(this.rings).forEach(key => {
                    const ring = this.rings[key];
                    ring.children.forEach(child => {
                        if (child.material?.opacity !== undefined) {
                            child.material.opacity = key === axis ? 1 : 0.5;
                        }
                    });
                });
            }
        };

        const onMouseUp = () => {
            if (isDragging) {
                isDragging = false;
                dragAxis = null;
                this.activeRing = null;
                if (this.controls) this.controls.enabled = true;

                // Reset opacity
                Object.values(this.rings).forEach(ring => {
                    ring.children.forEach(child => {
                        if (child.material?.opacity !== undefined) {
                            child.material.opacity = 0.8;
                        }
                    });
                });
            }
        };

        this.canvas.addEventListener('mousedown', onMouseDown);
        this.canvas.addEventListener('mousemove', onMouseMove);
        this.canvas.addEventListener('mouseup', onMouseUp);
        window.addEventListener('mouseup', onMouseUp);
    }

    /**
     * Show gizmo at mesh position.
     */
    show(mesh, controls) {
        this.targetMesh = mesh;
        this.controls = controls;
        
        if (mesh) {
            this.group.position.copy(mesh.position);
            this.group.visible = true;
            this.isActive = true;
        }
    }

    /**
     * Hide gizmo.
     */
    hide() {
        this.group.visible = false;
        this.isActive = false;
        this.targetMesh = null;
    }

    /**
     * Update gizmo position.
     */
    update() {
        if (this.targetMesh && this.group.visible) {
            this.group.position.copy(this.targetMesh.position);
        }
    }
}

// Export
window.ScaleGizmo = ScaleGizmo;
window.RotateGizmo = RotateGizmo;

