/**
 * ============================================
 * 3D Platform - OrbitControls Definition
 * ============================================
 * 
 * PURPOSE:
 * Provides camera orbit controls for the 3D viewport.
 * This is a self-contained implementation of OrbitControls.
 * 
 * DEPENDENCIES:
 * - Three.js (loaded globally)
 * 
 * EXPORTS:
 * - OrbitControls class (global)
 * 
 * ============================================
 */

(function() {
    'use strict';

    // Exit if already defined or Three.js not loaded
    if (typeof window.OrbitControls !== 'undefined') return;
    if (typeof THREE === 'undefined') {
        console.warn('OrbitControls: Three.js not loaded');
        return;
    }

    const _changeEvent = { type: "change" };
    const _startEvent = { type: "start" };
    const _endEvent = { type: "end" };

    class OrbitControls extends THREE.EventDispatcher {
        constructor(object, domElement) {
            super();
            
            this.object = object;
            this.domElement = domElement;
            this.domElement.style.touchAction = "none";

            // Settings
            this.enabled = true;
            this.target = new THREE.Vector3();
            this.minDistance = 0;
            this.maxDistance = Infinity;
            this.minPolarAngle = 0;
            this.maxPolarAngle = Math.PI;
            this.enableDamping = false;
            this.dampingFactor = 0.05;
            this.enableZoom = true;
            this.zoomSpeed = 1.0;
            this.enableRotate = true;
            this.rotateSpeed = 1.0;
            this.enablePan = true;
            this.panSpeed = 1.0;

            const scope = this;
            const STATE = { NONE: -1, ROTATE: 0, DOLLY: 1, PAN: 2 };
            let state = STATE.NONE;

            const spherical = new THREE.Spherical();
            const sphericalDelta = new THREE.Spherical();
            let scale = 1;
            const panOffset = new THREE.Vector3();

            const rotateStart = new THREE.Vector2();
            const rotateEnd = new THREE.Vector2();
            const rotateDelta = new THREE.Vector2();

            const panStart = new THREE.Vector2();
            const panEnd = new THREE.Vector2();
            const panDelta = new THREE.Vector2();

            const dollyStart = new THREE.Vector2();
            const dollyEnd = new THREE.Vector2();
            const dollyDelta = new THREE.Vector2();

            const offset = new THREE.Vector3();
            const quat = new THREE.Quaternion().setFromUnitVectors(object.up, new THREE.Vector3(0, 1, 0));
            const quatInverse = quat.clone().invert();
            const lastPosition = new THREE.Vector3();
            const lastQuaternion = new THREE.Quaternion();
            const EPS = 0.000001;

            this.update = function() {
                const position = scope.object.position;
                
                offset.copy(position).sub(scope.target);
                offset.applyQuaternion(quat);
                spherical.setFromVector3(offset);

                if (scope.enableDamping) {
                    spherical.theta += sphericalDelta.theta * scope.dampingFactor;
                    spherical.phi += sphericalDelta.phi * scope.dampingFactor;
                } else {
                    spherical.theta += sphericalDelta.theta;
                    spherical.phi += sphericalDelta.phi;
                }

                spherical.phi = Math.max(scope.minPolarAngle, Math.min(scope.maxPolarAngle, spherical.phi));
                spherical.makeSafe();
                spherical.radius *= scale;
                spherical.radius = Math.max(scope.minDistance, Math.min(scope.maxDistance, spherical.radius));

                if (scope.enableDamping) {
                    scope.target.addScaledVector(panOffset, scope.dampingFactor);
                } else {
                    scope.target.add(panOffset);
                }

                offset.setFromSpherical(spherical);
                offset.applyQuaternion(quatInverse);
                position.copy(scope.target).add(offset);
                scope.object.lookAt(scope.target);

                if (scope.enableDamping) {
                    sphericalDelta.theta *= (1 - scope.dampingFactor);
                    sphericalDelta.phi *= (1 - scope.dampingFactor);
                    panOffset.multiplyScalar(1 - scope.dampingFactor);
                } else {
                    sphericalDelta.set(0, 0, 0);
                    panOffset.set(0, 0, 0);
                }

                scale = 1;

                if (lastPosition.distanceToSquared(scope.object.position) > EPS ||
                    8 * (1 - lastQuaternion.dot(scope.object.quaternion)) > EPS) {
                    scope.dispatchEvent(_changeEvent);
                    lastPosition.copy(scope.object.position);
                    lastQuaternion.copy(scope.object.quaternion);
                }

                return false;
            };

            function rotateLeft(angle) {
                sphericalDelta.theta -= angle;
            }

            function rotateUp(angle) {
                sphericalDelta.phi -= angle;
            }

            const v = new THREE.Vector3();
            
            function panLeft(distance, objectMatrix) {
                v.setFromMatrixColumn(objectMatrix, 0);
                v.multiplyScalar(-distance);
                panOffset.add(v);
            }

            function panUp(distance, objectMatrix) {
                v.setFromMatrixColumn(objectMatrix, 1);
                v.multiplyScalar(distance);
                panOffset.add(v);
            }

            function pan(deltaX, deltaY) {
                const element = scope.domElement;
                const position = scope.object.position;
                
                offset.copy(position).sub(scope.target);
                let targetDistance = offset.length();
                targetDistance *= Math.tan((scope.object.fov / 2) * Math.PI / 180.0);

                panLeft(2 * deltaX * targetDistance / element.clientHeight, scope.object.matrix);
                panUp(2 * deltaY * targetDistance / element.clientHeight, scope.object.matrix);
            }

            function dollyOut(dollyScale) {
                scale /= dollyScale;
            }

            function dollyIn(dollyScale) {
                scale *= dollyScale;
            }

            function getZoomScale() {
                return Math.pow(0.95, scope.zoomSpeed);
            }

            function onPointerDown(event) {
                if (!scope.enabled) return;

                if (event.button === 0) {
                    state = STATE.ROTATE;
                    rotateStart.set(event.clientX, event.clientY);
                } else if (event.button === 1) {
                    state = STATE.DOLLY;
                    dollyStart.set(event.clientX, event.clientY);
                } else if (event.button === 2) {
                    state = STATE.PAN;
                    panStart.set(event.clientX, event.clientY);
                }

                if (state !== STATE.NONE) {
                    document.addEventListener("pointermove", onPointerMove);
                    document.addEventListener("pointerup", onPointerUp);
                }
            }

            function onPointerMove(event) {
                if (!scope.enabled) return;

                if (state === STATE.ROTATE) {
                    rotateEnd.set(event.clientX, event.clientY);
                    rotateDelta.subVectors(rotateEnd, rotateStart).multiplyScalar(scope.rotateSpeed);
                    rotateLeft(2 * Math.PI * rotateDelta.x / scope.domElement.clientHeight);
                    rotateUp(2 * Math.PI * rotateDelta.y / scope.domElement.clientHeight);
                    rotateStart.copy(rotateEnd);
                } else if (state === STATE.DOLLY) {
                    dollyEnd.set(event.clientX, event.clientY);
                    dollyDelta.subVectors(dollyEnd, dollyStart);
                    if (dollyDelta.y > 0) {
                        dollyOut(getZoomScale());
                    } else if (dollyDelta.y < 0) {
                        dollyIn(getZoomScale());
                    }
                    dollyStart.copy(dollyEnd);
                } else if (state === STATE.PAN) {
                    panEnd.set(event.clientX, event.clientY);
                    panDelta.subVectors(panEnd, panStart).multiplyScalar(scope.panSpeed);
                    pan(panDelta.x, panDelta.y);
                    panStart.copy(panEnd);
                }

                scope.update();
            }

            function onPointerUp() {
                document.removeEventListener("pointermove", onPointerMove);
                document.removeEventListener("pointerup", onPointerUp);
                state = STATE.NONE;
            }

            function onMouseWheel(event) {
                if (!scope.enabled || !scope.enableZoom) return;
                
                event.preventDefault();
                
                if (event.deltaY < 0) {
                    dollyIn(getZoomScale());
                } else if (event.deltaY > 0) {
                    dollyOut(getZoomScale());
                }
                
                scope.update();
            }

            function onContextMenu(event) {
                if (scope.enabled) {
                    event.preventDefault();
                }
            }

            scope.domElement.addEventListener("contextmenu", onContextMenu);
            scope.domElement.addEventListener("pointerdown", onPointerDown);
            scope.domElement.addEventListener("wheel", onMouseWheel, { passive: false });

            this.update();
        }
    }

    // Export globally
    window.OrbitControls = OrbitControls;

})();

