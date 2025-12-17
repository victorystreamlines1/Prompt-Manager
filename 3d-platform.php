<?php
/**
 * Fusion 3D Platform
 * Advanced 3D model editor and manipulation platform
 * Created: 2025-11-28
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fusion 3D Platform - Editor</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="apple-touch-icon" href="logo.png">
    <style>
        /* ═══════════════════════════════════════════════════════════════
           BASE STYLES & VARIABLES
           ═══════════════════════════════════════════════════════════════ */
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --accent: #8b5cf6;
            --bg-dark: #0f0f1a;
            --bg-darker: #0a0a12;
            --bg-card: #1a1a2e;
            --bg-hover: #252542;
            --text: #ffffff;
            --text-muted: #94a3b8;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --border: rgba(99, 102, 241, 0.2);
            --border-light: rgba(255, 255, 255, 0.05);
            --glow: rgba(99, 102, 241, 0.4);
            --aurora-1: #06b6d4;
            --aurora-2: #8b5cf6;
            --aurora-3: #6366f1;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--bg-dark);
            color: var(--text);
            height: 100vh;
            overflow: hidden;
        }
        
        /* Enhanced Animated Aurora Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(ellipse at 20% 20%, rgba(99, 102, 241, 0.2) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(139, 92, 246, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(6, 182, 212, 0.1) 0%, transparent 60%);
            pointer-events: none;
            z-index: -1;
            animation: auroraShift 15s ease-in-out infinite alternate;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(ellipse at 70% 30%, rgba(99, 102, 241, 0.1) 0%, transparent 40%),
                radial-gradient(ellipse at 30% 70%, rgba(139, 92, 246, 0.08) 0%, transparent 40%);
            pointer-events: none;
            z-index: -1;
            animation: auroraShift 20s ease-in-out infinite alternate-reverse;
        }

        @keyframes auroraShift {
            0% { opacity: 0.7; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.05); }
            100% { opacity: 0.8; transform: scale(1.02); }
        }
        
        /* ═══════════════════════════════════════════════════════════════
           LAYOUT
           ═══════════════════════════════════════════════════════════════ */
        .platform-layout {
            display: grid;
            grid-template-rows: auto 1fr;
            grid-template-columns: 280px 1fr 320px;
            height: 100vh;
        }
        
        /* ═══════════════════════════════════════════════════════════════
           HEADER
           ═══════════════════════════════════════════════════════════════ */
        .header {
            grid-column: 1 / -1;
            background: linear-gradient(180deg, rgba(26, 26, 46, 0.98) 0%, rgba(26, 26, 46, 0.92) 100%);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(15px);
            position: relative;
            z-index: 100;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), var(--accent), transparent);
            opacity: 0.5;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 15px var(--glow);
            animation: logoPulse 3s ease-in-out infinite;
            position: relative;
        }

        .logo-icon::before {
            content: '';
            position: absolute;
            inset: -2px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 12px;
            z-index: -1;
            opacity: 0.5;
            filter: blur(8px);
            animation: logoGlow 3s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes logoGlow {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.6; }
        }
        
        .logo-text h1 {
            font-size: 1.2em;
            background: linear-gradient(135deg, var(--primary-light), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .logo-text span {
            font-size: 0.75em;
            color: var(--text-muted);
        }
        
        .model-name {
            padding: 8px 16px;
            background: var(--bg-hover);
            border-radius: 8px;
            border: 1px solid var(--border);
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .model-name .badge {
            padding: 2px 8px;
            background: var(--accent);
            border-radius: 4px;
            font-size: 0.75em;
            font-weight: bold;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        /* ═══════════════════════════════════════════════════════════════
           BUTTONS
           ═══════════════════════════════════════════════════════════════ */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-size: 0.9em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px var(--glow);
        }
        
        .btn-secondary {
            background: var(--bg-hover);
            color: var(--text);
            border: 1px solid var(--border);
        }
        
        .btn-secondary:hover {
            background: rgba(99, 102, 241, 0.2);
            border-color: var(--primary);
        }
        
        .btn-accent {
            background: linear-gradient(135deg, var(--accent), #a855f7);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }
        
        /* ═══════════════════════════════════════════════════════════════
           LEFT TOOLBAR
           ═══════════════════════════════════════════════════════════════ */
        .toolbar-left {
            background: linear-gradient(180deg, var(--bg-card) 0%, rgba(26, 26, 46, 0.95) 100%);
            border-right: 1px solid var(--border);
            padding: 15px;
            overflow-y: auto;
            position: relative;
        }

        .toolbar-left::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 1px;
            height: 100%;
            background: linear-gradient(180deg, var(--primary), transparent, var(--accent));
            opacity: 0.3;
        }

        /* Custom Scrollbar */
        .toolbar-left::-webkit-scrollbar,
        .panel-right::-webkit-scrollbar {
            width: 6px;
        }

        .toolbar-left::-webkit-scrollbar-track,
        .panel-right::-webkit-scrollbar-track {
            background: var(--bg-darker);
        }

        .toolbar-left::-webkit-scrollbar-thumb,
        .panel-right::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        .toolbar-left::-webkit-scrollbar-thumb:hover,
        .panel-right::-webkit-scrollbar-thumb:hover {
            background: var(--primary-light);
        }
        
        .tool-section {
            margin-bottom: 20px;
        }
        
        .tool-section-header {
            font-size: 0.75em;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }
        
        .tool-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        
        .tool-btn {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            background: var(--bg-hover);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.2em;
        }
        
        .tool-btn span {
            font-size: 0.55em;
            color: var(--text-muted);
        }
        
        .tool-btn:hover, .tool-btn.active {
            background: rgba(99, 102, 241, 0.2);
            border-color: var(--primary);
            color: var(--primary-light);
        }
        
        .tool-btn.active {
            box-shadow: 0 0 15px var(--glow);
        }
        
        /* ═══════════════════════════════════════════════════════════════
           MAIN VIEWPORT
           ═══════════════════════════════════════════════════════════════ */
        .viewport {
            background: 
                radial-gradient(ellipse at center, rgba(99, 102, 241, 0.03) 0%, transparent 70%),
                linear-gradient(135deg, #0a0a14 0%, #12121f 100%);
            position: relative;
            overflow: hidden;
        }

        .viewport::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                linear-gradient(90deg, rgba(99, 102, 241, 0.02) 1px, transparent 1px),
                linear-gradient(rgba(99, 102, 241, 0.02) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            opacity: 0.5;
        }
        
        #viewer-canvas {
            width: 100%;
            height: 100%;
            position: relative;
            z-index: 1;
        }
        
        .viewport-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: var(--text-muted);
            z-index: 10;
        }
        
        .viewport-overlay .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid var(--border);
            border-top-color: var(--primary);
            border-right-color: var(--accent);
            border-radius: 50%;
            margin: 0 auto 20px;
            animation: spin 0.8s linear infinite;
            box-shadow: 0 0 30px var(--glow);
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .viewport-info {
            position: absolute;
            bottom: 15px;
            left: 15px;
            display: flex;
            gap: 10px;
        }
        
        .info-badge {
            padding: 6px 12px;
            background: rgba(26, 26, 46, 0.9);
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.8em;
            backdrop-filter: blur(5px);
        }
        
        .viewport-controls {
            position: absolute;
            bottom: 15px;
            right: 15px;
            display: flex;
            gap: 8px;
        }
        
        .viewport-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(26, 26, 46, 0.9);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.1em;
            backdrop-filter: blur(5px);
        }
        
        .viewport-btn:hover {
            background: var(--primary);
            border-color: var(--primary);
        }
        
        /* ═══════════════════════════════════════════════════════════════
           RIGHT PANEL - PROPERTIES
           ═══════════════════════════════════════════════════════════════ */
        .panel-right {
            background: linear-gradient(180deg, var(--bg-card) 0%, rgba(26, 26, 46, 0.95) 100%);
            border-left: 1px solid var(--border);
            overflow-y: auto;
            position: relative;
        }

        .panel-right::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 1px;
            height: 100%;
            background: linear-gradient(180deg, var(--primary), transparent, var(--accent));
            opacity: 0.3;
        }
        
        .panel-section {
            border-bottom: 1px solid var(--border);
        }
        
        .panel-header {
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .panel-header:hover {
            background: var(--bg-hover);
        }
        
        .panel-header h3 {
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .panel-toggle {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-hover);
            border-radius: 4px;
            font-size: 0.8em;
            transition: transform 0.2s;
        }
        
        .panel-section.collapsed .panel-toggle {
            transform: rotate(-90deg);
        }
        
        .panel-content {
            padding: 0 15px 15px;
        }
        
        .panel-section.collapsed .panel-content {
            display: none;
        }
        
        /* Property controls */
        .property-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .property-row:last-child {
            border-bottom: none;
        }
        
        .property-label {
            font-size: 0.85em;
            color: var(--text-muted);
        }
        
        .property-value {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .property-input {
            width: 70px;
            padding: 6px 10px;
            background: var(--bg-hover);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            font-size: 0.85em;
            text-align: center;
        }
        
        .property-input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .color-swatch {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            border: 2px solid var(--border);
            cursor: pointer;
        }
        
        /* Slider */
        .slider-container {
            width: 100%;
        }
        
        .slider {
            width: 100%;
            -webkit-appearance: none;
            height: 6px;
            background: var(--bg-hover);
            border-radius: 3px;
            outline: none;
        }
        
        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 8px var(--glow);
        }
        
        /* Export options */
        .export-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-top: 10px;
        }
        
        .export-btn {
            padding: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            background: var(--bg-hover);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .export-btn:hover {
            background: rgba(99, 102, 241, 0.2);
            border-color: var(--primary);
        }
        
        .export-btn span:first-child {
            font-size: 1.5em;
        }
        
        .export-btn span:last-child {
            font-size: 0.75em;
            color: var(--text-muted);
        }
        
        /* ═══════════════════════════════════════════════════════════════
           TOAST NOTIFICATIONS
           ═══════════════════════════════════════════════════════════════ */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 1000;
        }
        
        .toast {
            padding: 12px 20px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .toast.success { border-color: var(--success); }
        .toast.error { border-color: var(--error); }
        .toast.warning { border-color: var(--warning); }
        
        @keyframes slideIn {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* ═══════════════════════════════════════════════════════════════
           MODAL
           ═══════════════════════════════════════════════════════════════ */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            width: 90%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 40px var(--glow);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }
        
        .modal-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1em;
        }
        
        .modal-close {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-hover);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-muted);
            font-size: 1.5em;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .modal-close:hover {
            background: var(--error);
            border-color: var(--error);
            color: white;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-body p {
            color: var(--text-muted);
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .modal-input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .modal-input-group label {
            font-size: 0.85em;
            color: var(--text-muted);
        }
        
        .modal-input-group input {
            padding: 12px 16px;
            background: var(--bg-hover);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 1em;
            transition: all 0.2s;
        }
        
        .modal-input-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--glow);
        }
        
        .modal-footer {
            display: flex;
            gap: 10px;
            padding: 20px;
            border-top: 1px solid var(--border);
            justify-content: flex-end;
        }
        
        /* ═══════════════════════════════════════════════════════════════
           SCALE GIZMO INPUT POPUP
           ═══════════════════════════════════════════════════════════════ */
        .scale-input-popup {
            position: fixed;
            display: none;
            background: rgba(26, 26, 46, 0.95);
            border: 2px solid var(--primary);
            border-radius: 8px;
            padding: 8px 12px;
            z-index: 10000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            min-width: 120px;
        }
        
        .scale-input-popup.active {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .scale-input-popup label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .scale-input-popup .input-row {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .scale-input-popup input {
            width: 70px;
            padding: 6px 8px;
            background: var(--bg-dark);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text);
            font-size: 14px;
            font-weight: 600;
            text-align: center;
        }
        
        .scale-input-popup input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .scale-input-popup .unit {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .scale-input-popup.x-axis { border-color: #ef4444; }
        .scale-input-popup.y-axis { border-color: #22c55e; }
        .scale-input-popup.z-axis { border-color: #3b82f6; }
        .scale-input-popup.uniform { border-color: #f59e0b; }
        
        .scale-input-popup.x-axis label { color: #ef4444; }
        .scale-input-popup.y-axis label { color: #22c55e; }
        .scale-input-popup.z-axis label { color: #3b82f6; }
        .scale-input-popup.uniform label { color: #f59e0b; }
        
        /* Scale tool active indicator */
        .tool-btn[data-tool="scale"].active {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
            border-color: #f59e0b !important;
        }
        
        /* Rotate tool active indicator */
        .tool-btn[data-tool="rotate"].active {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed) !important;
            border-color: #8b5cf6 !important;
        }
        
        /* Rotation popup */
        .rotate-input-popup {
            position: fixed;
            display: none;
            background: rgba(26, 26, 46, 0.95);
            border: 2px solid var(--accent);
            border-radius: 8px;
            padding: 8px 12px;
            z-index: 10000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            min-width: 120px;
        }
        
        .rotate-input-popup.active {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .rotate-input-popup label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .rotate-input-popup .input-row {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .rotate-input-popup input {
            width: 70px;
            padding: 6px 8px;
            background: var(--bg-dark);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text);
            font-size: 14px;
            font-weight: 600;
            text-align: center;
        }
        
        .rotate-input-popup input:focus {
            outline: none;
            border-color: var(--accent);
        }
        
        .rotate-input-popup .unit {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .rotate-input-popup.x-axis { border-color: #ef4444; }
        .rotate-input-popup.y-axis { border-color: #22c55e; }
        .rotate-input-popup.z-axis { border-color: #3b82f6; }
        .rotate-input-popup.free { border-color: #8b5cf6; }
        
        .rotate-input-popup.x-axis label { color: #ef4444; }
        .rotate-input-popup.y-axis label { color: #22c55e; }
        .rotate-input-popup.z-axis label { color: #3b82f6; }
        .rotate-input-popup.free label { color: #8b5cf6; }
        
        /* ═══════════════════════════════════════════════════════════════
           EXPORT PANEL & TOGGLE SWITCHES
           ═══════════════════════════════════════════════════════════════ */
        .export-panel {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(26, 26, 46, 0.8);
            padding: 8px 15px;
            border-radius: 10px;
            border: 1px solid var(--border);
        }
        
        .export-toggles {
            display: flex;
            gap: 12px;
        }
        
        .toggle-switch {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            user-select: none;
        }
        
        .toggle-switch input {
            display: none;
        }
        
        .toggle-slider {
            width: 36px;
            height: 20px;
            background: #3a3a5a;
            border-radius: 10px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .toggle-slider::before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: all 0.3s ease;
        }
        
        .toggle-switch input:checked + .toggle-slider {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .toggle-switch input:checked + .toggle-slider::before {
            left: 18px;
        }
        
        .toggle-label {
            font-size: 0.85em;
            font-weight: 600;
            color: var(--text-muted);
            transition: color 0.3s;
        }
        
        .toggle-switch input:checked ~ .toggle-label {
            color: #10b981;
        }
        
        .btn-export {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .btn-export:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .export-formats-display {
            margin-top: 15px;
            padding: 12px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .export-formats-display .format-label {
            font-size: 0.85em;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        
        .export-formats-display .format-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .export-formats-display .format-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .export-formats-display .format-badge span {
            font-size: 1em;
        }
        
        /* ═══════════════════════════════════════════════════════════════
           NO DATA MESSAGE
           ═══════════════════════════════════════════════════════════════ */
        .no-data {
            grid-column: 1 / -1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: calc(100vh - 60px);
            text-align: center;
        }
        
        .no-data-icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .no-data h2 {
            margin-bottom: 10px;
            color: var(--text-muted);
        }
        
        .no-data p {
            color: var(--text-muted);
            margin-bottom: 20px;
        }
        
        /* ═══════════════════════════════════════════════════════════════
           HOME BUTTON (In Header Row)
           ═══════════════════════════════════════════════════════════════ */
        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.15));
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9em;
            transition: all 0.2s ease;
        }

        .btn-home:hover {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 6px 25px var(--glow);
        }
        
        .btn-home span:first-child {
            font-size: 1em;
        }
    </style>
</head>
<body>
    <div class="platform-layout">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <div class="logo">
                    <div class="logo-icon">🎨</div>
                    <div class="logo-text">
                        <h1>Fusion Platform</h1>
                        <span>3D Editor</span>
                    </div>
                </div>
                <div class="model-name" id="model-name-display">
                    <span>📦</span>
                    <span id="filename">Loading...</span>
                    <span class="badge" id="filetype-badge">---</span>
                </div>
            </div>
            <div class="header-actions">
                <a href="index.php" class="btn btn-home" title="Back to Home">
                    <span>🏠</span> Home
                </a>
                <button class="btn btn-secondary" id="upload-btn" title="Upload 3D model or HTML">
                    <span>📂</span> Upload
                </button>
                <button class="btn btn-secondary" id="clear-btn" title="Clear platform">
                    <span>🗑️</span> Clear
                </button>
                <button class="btn btn-secondary" id="reset-default-btn" title="Reset all changes to default">
                    <span>🔄</span> Reset Default
                </button>
                <a href="viewer.php" class="btn btn-secondary">
                    <span>←</span> Back to Viewer
                </a>
                
                <!-- Save HTML Button (for STL/OBJ/FBX/GLB files) -->
                <button class="btn btn-success" id="save-btn" style="display: none;">
                    <span>💾</span> Save HTML
                </button>
                
                <!-- Export Panel (for HTML files) -->
                <div class="export-panel" id="export-panel" style="display: none;">
                    <div class="export-toggles">
                        <label class="toggle-switch">
                            <input type="checkbox" id="export-stl" checked>
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">STL</span>
                        </label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="export-obj">
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">OBJ</span>
                        </label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="export-glb">
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">GLB</span>
                        </label>
                        <!-- FBX export hidden - not supported in browser -->
                        <label class="toggle-switch" style="display: none;">
                            <input type="checkbox" id="export-fbx" disabled>
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">FBX</span>
                        </label>
                    </div>
                    <button class="btn btn-export" id="export-selected-btn">
                        <span>⬇️</span> Export
                    </button>
                </div>
            </div>
            <!-- Hidden file input for upload -->
            <input type="file" id="file-upload-input" accept=".stl,.obj,.fbx,.glb,.gltf,.html,.htm" style="display: none;">
        </header>
        
        <!-- Left Toolbar -->
        <aside class="toolbar-left" id="toolbar-left">
            <div class="tool-section">
                <div class="tool-section-header">Transform</div>
                <div class="tool-grid">
                    <button class="tool-btn active" data-tool="select" title="Select">
                        🔲
                        <span>Select</span>
                    </button>
                    <button class="tool-btn" data-tool="move" title="Move">
                        ✥
                        <span>Move</span>
                    </button>
                    <button class="tool-btn" data-tool="rotate" title="Rotate">
                        🔄
                        <span>Rotate</span>
                    </button>
                    <button class="tool-btn" data-tool="scale" title="Scale">
                        ⤡
                        <span>Scale</span>
                    </button>
                </div>
            </div>
            
            <div class="tool-section">
                <div class="tool-section-header">View</div>
                <div class="tool-grid">
                    <button class="tool-btn" data-action="reset-view" title="Reset View">
                        🎯
                        <span>Reset</span>
                    </button>
                    <button class="tool-btn" data-action="zoom-fit" title="Zoom to Fit">
                        🔍
                        <span>Fit</span>
                    </button>
                    <button class="tool-btn" data-action="wireframe" title="Wireframe">
                        📐
                        <span>Wire</span>
                    </button>
                    <button class="tool-btn" data-action="grid-toggle" title="Toggle Grid">
                        #
                        <span>Grid</span>
                    </button>
                </div>
            </div>
            
            <div class="tool-section">
                <div class="tool-section-header">Camera Presets</div>
                <div class="tool-grid">
                    <button class="tool-btn" data-camera="front" title="Front View">
                        ⬆
                        <span>Front</span>
                    </button>
                    <button class="tool-btn" data-camera="back" title="Back View">
                        ⬇
                        <span>Back</span>
                    </button>
                    <button class="tool-btn" data-camera="left" title="Left View">
                        ⬅
                        <span>Left</span>
                    </button>
                    <button class="tool-btn" data-camera="right" title="Right View">
                        ➡
                        <span>Right</span>
                    </button>
                    <button class="tool-btn" data-camera="top" title="Top View">
                        ⏫
                        <span>Top</span>
                    </button>
                    <button class="tool-btn" data-camera="perspective" title="Perspective">
                        🎥
                        <span>3D</span>
                    </button>
                </div>
            </div>
        </aside>
        
        <!-- Main Viewport -->
        <main class="viewport" id="viewport">
            <div id="viewer-canvas"></div>
            <div class="viewport-overlay" id="loading-overlay">
                <div class="spinner"></div>
                <p id="loading-status">Loading model...</p>
            </div>
            <div class="viewport-info">
                <div class="info-badge" id="vertex-count">Vertices: ---</div>
                <div class="info-badge" id="triangle-count">Triangles: ---</div>
            </div>
            <div class="viewport-controls">
                <button class="viewport-btn" title="Reset Camera" data-action="reset-view">🎯</button>
                <button class="viewport-btn" title="Toggle Wireframe" data-action="wireframe">📐</button>
                <button class="viewport-btn" title="Fullscreen" data-action="fullscreen">⛶</button>
            </div>
        </main>
        
        <!-- Right Panel -->
        <aside class="panel-right" id="panel-right">
            <!-- Transform Section -->
            <div class="panel-section">
                <div class="panel-header">
                    <h3><span>📐</span> Transform</h3>
                    <span class="panel-toggle">▼</span>
                </div>
                <div class="panel-content">
                    <div class="property-row">
                        <span class="property-label">Position X</span>
                        <div class="property-value">
                            <input type="number" class="property-input" id="pos-x" value="0" step="0.1">
                        </div>
                    </div>
                    <div class="property-row">
                        <span class="property-label">Position Y</span>
                        <div class="property-value">
                            <input type="number" class="property-input" id="pos-y" value="0" step="0.1">
                        </div>
                    </div>
                    <div class="property-row">
                        <span class="property-label">Position Z</span>
                        <div class="property-value">
                            <input type="number" class="property-input" id="pos-z" value="0" step="0.1">
                        </div>
                    </div>
                    <div class="property-row">
                        <span class="property-label">Scale</span>
                        <div class="property-value">
                            <input type="number" class="property-input" id="scale-uniform" value="1" step="0.1" min="0.1">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Material Section -->
            <div class="panel-section">
                <div class="panel-header">
                    <h3><span>🎨</span> Material</h3>
                    <span class="panel-toggle">▼</span>
                </div>
                <div class="panel-content">
                    <div class="property-row">
                        <span class="property-label">Color</span>
                        <div class="property-value">
                            <input type="color" class="color-swatch" id="material-color" value="#6366f1">
                        </div>
                    </div>
                    <div class="property-row">
                        <span class="property-label">Metalness</span>
                        <div class="slider-container">
                            <input type="range" class="slider" id="metalness" min="0" max="1" step="0.1" value="0.3">
                        </div>
                    </div>
                    <div class="property-row">
                        <span class="property-label">Roughness</span>
                        <div class="slider-container">
                            <input type="range" class="slider" id="roughness" min="0" max="1" step="0.1" value="0.7">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Scene Section -->
            <div class="panel-section">
                <div class="panel-header">
                    <h3><span>🌍</span> Scene</h3>
                    <span class="panel-toggle">▼</span>
                </div>
                <div class="panel-content">
                    <div class="property-row">
                        <span class="property-label">Background</span>
                        <div class="property-value">
                            <input type="color" class="color-swatch" id="bg-color" value="#1a1a2e">
                        </div>
                    </div>
                    <div class="property-row">
                        <span class="property-label">Grid Opacity</span>
                        <div class="slider-container">
                            <input type="range" class="slider" id="grid-opacity" min="0" max="1" step="0.1" value="0.3">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Export Section -->
            <div class="panel-section">
                <div class="panel-header">
                    <h3><span>💾</span> Export</h3>
                    <span class="panel-toggle">▼</span>
                </div>
                <div class="panel-content">
                    <div class="export-grid">
                        <button class="export-btn" data-export="html">
                            <span>🌐</span>
                            <span>HTML Viewer</span>
                        </button>
                        <button class="export-btn" data-export="stl">
                            <span>📦</span>
                            <span>STL</span>
                        </button>
                        <button class="export-btn" data-export="obj">
                            <span>📦</span>
                            <span>OBJ</span>
                        </button>
                        <button class="export-btn" data-export="glb">
                            <span>📦</span>
                            <span>GLB</span>
                        </button>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- No Data Fallback -->
        <div class="no-data" id="no-data" style="display: none;">
            <div class="no-data-icon">🔮</div>
            <h2>No Model Data</h2>
            <p>Send a model from the Fusion Viewer to start editing</p>
            <a href="viewer.php" class="btn btn-primary">
                <span>←</span> Go to Viewer
            </a>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toast-container"></div>
    
    <!-- Save HTML Modal -->
    <div class="modal-overlay" id="save-modal">
        <div class="modal">
            <div class="modal-header">
                <h3><span>💾</span> Save HTML</h3>
                <button class="modal-close" id="save-modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Enter a name for your project. It will be saved as an HTML viewer file.</p>
                <div class="modal-input-group">
                    <label for="save-filename">Project Name</label>
                    <input type="text" id="save-filename" placeholder="my-project">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="save-modal-cancel">Cancel</button>
                <button class="btn btn-success" id="save-modal-confirm">
                    <span>💾</span> Save & Download
                </button>
            </div>
        </div>
    </div>
    
    <!-- Export Name Modal -->
    <div class="modal-overlay" id="export-modal">
        <div class="modal">
            <div class="modal-header">
                <h3><span>⬇️</span> Export Model</h3>
                <button class="modal-close" id="export-modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Enter a name for your exported file(s).</p>
                <div class="modal-input-group">
                    <label for="export-filename">File Name</label>
                    <input type="text" id="export-filename" placeholder="model">
                </div>
                <div class="export-formats-display" id="export-formats-display">
                    <!-- Will be populated dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="export-modal-cancel">Cancel</button>
                <button class="btn btn-export" id="export-modal-confirm">
                    <span>⬇️</span> Export
                </button>
            </div>
        </div>
    </div>
    
    <!-- Scale Input Popup -->
    <div class="scale-input-popup" id="scale-popup">
        <label id="scale-popup-label">Scale X</label>
        <div class="input-row">
            <input type="number" id="scale-popup-input" step="0.01" min="0.01" value="1.00">
            <span class="unit">×</span>
        </div>
    </div>
    
    <!-- Rotation Input Popup -->
    <div class="rotate-input-popup" id="rotate-popup">
        <label id="rotate-popup-label">Rotate X</label>
        <div class="input-row">
            <input type="number" id="rotate-popup-input" step="1" value="0">
            <span class="unit">°</span>
        </div>
    </div>
    
    <!-- Three.js with FBX Support and GLTFExporter -->
    <script type="importmap">
    {
        "imports": {
            "three": "https://unpkg.com/three@0.160.0/build/three.module.js",
            "three/addons/": "https://unpkg.com/three@0.160.0/examples/jsm/"
        }
    }
    </script>
    <script src="https://unpkg.com/three@0.160.0/build/three.min.js"></script>
    <script type="module">
        // Import loaders and exporters as ES modules and make them global
        try {
            const { FBXLoader } = await import('three/addons/loaders/FBXLoader.js');
            window.FBXLoader = FBXLoader;
            window.fbxLoaderReady = true;
            console.log('✓ FBXLoader ready');
        } catch (e) {
            console.error('FBXLoader failed to load:', e);
        }
        
        try {
            const { GLTFLoader } = await import('three/addons/loaders/GLTFLoader.js');
            window.GLTFLoader = GLTFLoader;
            window.gltfLoaderReady = true;
            console.log('✓ GLTFLoader ready');
        } catch (e) {
            console.error('GLTFLoader failed to load:', e);
        }
        
        try {
            const { GLTFExporter } = await import('three/addons/exporters/GLTFExporter.js');
            THREE.GLTFExporter = GLTFExporter;
            window.gltfExporterReady = true;
            console.log('✓ GLTFExporter ready');
        } catch (e) {
            console.error('GLTFExporter failed to load:', e);
        }
    </script>
    <script>
        // ═══════════════════════════════════════════════════════════════
        // PLATFORM STATE
        // ═══════════════════════════════════════════════════════════════
        const platformState = {
            geometryData: null,
            fileType: null,
            mesh: null,
            scene: null,
            camera: null,
            renderer: null,
            controls: null,
            gridHelper: null,
            isWireframe: false,
            gridVisible: true
        };
        
        // ═══════════════════════════════════════════════════════════════
        // INITIALIZATION (Using IndexedDB for large models)
        // ═══════════════════════════════════════════════════════════════
        
        // IndexedDB helper with timeout protection
        const PlatformDB = {
            dbName: 'FusionPlatformDB',
            storeName: 'geometryStore',
            timeout: 3000, // 3 second timeout
            
            // Wrap promise with timeout
            withTimeout(promise, ms) {
                return Promise.race([
                    promise,
                    new Promise((_, reject) => 
                        setTimeout(() => reject(new Error('IndexedDB timeout')), ms)
                    )
                ]);
            },
            
            open() {
                return this.withTimeout(new Promise((resolve, reject) => {
                    if (!window.indexedDB) {
                        reject(new Error('IndexedDB not supported'));
                        return;
                    }
                    const request = indexedDB.open(this.dbName, 1);
                    request.onerror = () => reject(request.error);
                    request.onsuccess = () => resolve(request.result);
                    request.onupgradeneeded = (e) => {
                        const db = e.target.result;
                        if (!db.objectStoreNames.contains(this.storeName)) {
                            db.createObjectStore(this.storeName, { keyPath: 'id' });
                        }
                    };
                }), this.timeout);
            },
            
            async load() {
                const db = await this.open();
                return this.withTimeout(new Promise((resolve, reject) => {
                    const tx = db.transaction(this.storeName, 'readonly');
                    const store = tx.objectStore(this.storeName);
                    const request = store.get('platformData');
                    request.onsuccess = () => { db.close(); resolve(request.result); };
                    request.onerror = () => { db.close(); reject(request.error); };
                }), this.timeout);
            },
            
            async clear() {
                const db = await this.open();
                return this.withTimeout(new Promise((resolve, reject) => {
                    const tx = db.transaction(this.storeName, 'readwrite');
                    const store = tx.objectStore(this.storeName);
                    const request = store.delete('platformData');
                    request.onsuccess = () => { db.close(); resolve(true); };
                    request.onerror = () => { db.close(); reject(request.error); };
                }), this.timeout);
            }
        };
        
        // Load geometry data from IndexedDB
        async function loadFromStorage() {
            try {
                const data = await PlatformDB.load();
                
                if (!data || !data.geometry) {
                    console.log('No geometry data in storage, showing empty scene');
                    initEmptyPlatform();
                    return;
                }
                
                platformState.geometryData = data.geometry;
                platformState.fileType = data.fileType || 'unknown';
                
                // Update UI with file info
                document.getElementById('filename').textContent = platformState.geometryData.name || 'Untitled';
                document.getElementById('filetype-badge').textContent = platformState.fileType.toUpperCase();
                
                console.log('✓ Loaded geometry from IndexedDB:', {
                    name: platformState.geometryData.name,
                    vertices: platformState.geometryData.vertices.length / 3
                });
                
                // Wait for Three.js then initialize with geometry
                initPlatform(true);
                
            } catch (error) {
                console.warn('IndexedDB load failed:', error.message);
                // Show empty scene instead of error
                initEmptyPlatform();
            }
        }
        
        // Initialize empty platform (no model, just grid)
        function initEmptyPlatform() {
            console.log('Initializing empty platform with grid...');
            
            // Update header to show ready state
            document.getElementById('filename').textContent = 'Ready to Upload';
            document.getElementById('filetype-badge').textContent = '---';
            
            // Initialize the 3D scene without geometry
            initPlatform(false);
        }
        
        // Early toast function for initialization errors
        function earlyToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `<span>${type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'}</span> ${message}`;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
        
        // GLOBAL SAFETY NET: Hide loading after 8 seconds no matter what
        setTimeout(() => {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                const style = window.getComputedStyle(overlay);
                if (style.display !== 'none') {
                    console.warn('⚠ Global safety timeout triggered - hiding loading overlay');
                    overlay.style.display = 'none';
                    earlyToast('Loading took too long. Scene may not be fully ready.', 'warning');
                }
            }
        }, 8000);
        
        // Start loading with Three.js availability check
        let threeJsCheckCount = 0;
        const maxThreeJsChecks = 50; // 5 seconds max wait
        
        function waitForThreeAndLoad() {
            threeJsCheckCount++;
            
            if (typeof THREE !== 'undefined') {
                console.log('✓ Three.js loaded after ' + (threeJsCheckCount * 100) + 'ms');
                loadFromStorage();
            } else if (threeJsCheckCount >= maxThreeJsChecks) {
                console.error('Three.js failed to load after 5 seconds');
                document.getElementById('loading-overlay').style.display = 'none';
                earlyToast('Failed to load 3D library. Please refresh the page.', 'error');
            } else {
                setTimeout(waitForThreeAndLoad, 100);
            }
        }
        
        // Start the loading process
        waitForThreeAndLoad();
        
        function initPlatform(hasGeometry = true) {
            // Always hide loading after a maximum timeout (safety net)
            setTimeout(() => {
                const overlay = document.getElementById('loading-overlay');
                if (overlay && overlay.style.display !== 'none') {
                    overlay.style.display = 'none';
                    console.warn('Loading overlay hidden by safety timeout');
                }
            }, 5000);
            
            if (typeof THREE === 'undefined') {
                console.log('Waiting for Three.js to load...');
                setTimeout(() => initPlatform(hasGeometry), 100);
                return;
            }
            
            try {
                console.log('Initializing platform, hasGeometry:', hasGeometry);
                
                defineOrbitControls();
                setupScene();
                
                // Only load geometry if we have data
                if (hasGeometry && platformState.geometryData) {
                    loadGeometry();
                    showToast('Model loaded successfully', 'success');
                } else {
                    // Show empty scene message
                    document.getElementById('vertex-count').textContent = 'Vertices: 0';
                    document.getElementById('triangle-count').textContent = 'Triangles: 0';
                    showToast('Ready! Upload a 3D model to start', 'info');
                }
                
                setupEventListeners();
                animate();
                
                console.log('✓ Platform initialized successfully');
                
            } catch (error) {
                console.error('Platform initialization error:', error);
                showToast('Error initializing 3D viewer: ' + error.message, 'error');
            } finally {
                // Always hide loading overlay
                document.getElementById('loading-overlay').style.display = 'none';
            }
            
            // Update save button visibility after loading
            setTimeout(() => updateSaveButtonVisibility(), 100);
        }
        
        // ═══════════════════════════════════════════════════════════════
        // THREE.JS SETUP
        // ═══════════════════════════════════════════════════════════════
        
        function setupScene() {
            const container = document.getElementById('viewer-canvas');
            
            // Get container dimensions with fallback
            const width = container.clientWidth || window.innerWidth * 0.6;
            const height = container.clientHeight || window.innerHeight - 100;
            
            // Scene
            platformState.scene = new THREE.Scene();
            platformState.scene.background = new THREE.Color('#1a1a2e');
            
            // Camera
            platformState.camera = new THREE.PerspectiveCamera(
                45,
                width / height,
                0.01,
                1000
            );
            platformState.camera.position.set(5, 5, 5);
            
            // Renderer
            platformState.renderer = new THREE.WebGLRenderer({ antialias: true });
            platformState.renderer.setSize(width, height);
            platformState.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(platformState.renderer.domElement);
            
            // Controls
            platformState.controls = new OrbitControls(platformState.camera, platformState.renderer.domElement);
            platformState.controls.enableDamping = true;
            platformState.controls.dampingFactor = 0.05;
            
            // Lights
            const ambient = new THREE.AmbientLight(0xffffff, 0.6);
            platformState.scene.add(ambient);
            
            const mainLight = new THREE.DirectionalLight(0xffffff, 1);
            mainLight.position.set(5, 10, 7);
            platformState.scene.add(mainLight);
            
            const fillLight = new THREE.DirectionalLight(0x6366f1, 0.3);
            fillLight.position.set(-5, 5, -5);
            platformState.scene.add(fillLight);
            
            // Grid
            platformState.gridHelper = new THREE.GridHelper(20, 40, 0x6366f1, 0x2a2a4a);
            platformState.gridHelper.material.opacity = 0.3;
            platformState.gridHelper.material.transparent = true;
            platformState.scene.add(platformState.gridHelper);
            
            // Resize handler
            window.addEventListener('resize', onWindowResize);
        }
        
        function loadGeometry() {
            const data = platformState.geometryData;
            if (!data || !data.vertices || data.vertices.length === 0) {
                showToast('Invalid geometry data', 'error');
                return;
            }
            
            // Remove existing mesh if present
            if (platformState.mesh && platformState.scene) {
                platformState.scene.remove(platformState.mesh);
                if (platformState.mesh.geometry) platformState.mesh.geometry.dispose();
                if (platformState.mesh.material) platformState.mesh.material.dispose();
            }
            
            const vertices = new Float32Array(data.vertices);
            const geometry = new THREE.BufferGeometry();
            geometry.setAttribute('position', new THREE.BufferAttribute(vertices, 3));
            geometry.computeVertexNormals();
            
            const material = new THREE.MeshStandardMaterial({
                color: 0x6366f1,
                metalness: 0.3,
                roughness: 0.7,
                side: THREE.DoubleSide
            });
            
            platformState.mesh = new THREE.Mesh(geometry, material);
            
            // Center and scale
            geometry.computeBoundingBox();
            const center = geometry.boundingBox.getCenter(new THREE.Vector3());
            const size = geometry.boundingBox.getSize(new THREE.Vector3());
            const maxDim = Math.max(size.x, size.y, size.z);
            const scale = 3 / maxDim;
            
            geometry.translate(-center.x, -center.y, -center.z);
            platformState.mesh.scale.setScalar(scale);
            
            // Update Scale UI
            const scaleInput = document.getElementById('scale-uniform');
            if (scaleInput) scaleInput.value = scale.toFixed(3);
            
            // Initialize saved rotation (will be updated by gizmo or from imported settings)
            platformState.savedRotation = { x: 0, y: 0, z: 0 };
            
            // Verify scene exists before adding mesh
            if (!platformState.scene) {
                console.error('Cannot add mesh - scene is null');
                showToast('3D scene not initialized', 'error');
                return;
            }
            platformState.scene.add(platformState.mesh);
            
            // Update stats
            const vertexCount = vertices.length / 3;
            const triangleCount = Math.floor(vertexCount / 3);
            document.getElementById('vertex-count').textContent = `Vertices: ${vertexCount.toLocaleString()}`;
            document.getElementById('triangle-count').textContent = `Triangles: ${triangleCount.toLocaleString()}`;
        }
        
        function onWindowResize() {
            const container = document.getElementById('viewer-canvas');
            platformState.camera.aspect = container.clientWidth / container.clientHeight;
            platformState.camera.updateProjectionMatrix();
            platformState.renderer.setSize(container.clientWidth, container.clientHeight);
        }
        
        function animate() {
            requestAnimationFrame(animate);
            platformState.controls.update();
            
            // Update all gizmo positions
            if (platformState.mesh) {
                if (scaleGizmo.group && scaleGizmo.group.visible) {
                    scaleGizmo.group.position.copy(platformState.mesh.position);
                }
                if (rotateGizmo.group && rotateGizmo.group.visible) {
                    rotateGizmo.group.position.copy(platformState.mesh.position);
                }
            }
            
            platformState.renderer.render(platformState.scene, platformState.camera);
        }
        
        // ═══════════════════════════════════════════════════════════════
        // SCALE GIZMO SYSTEM (Like Fusion 360)
        // ═══════════════════════════════════════════════════════════════
        
        const scaleGizmo = {
            group: null,
            handles: {},
            activeHandle: null,
            startMouse: new THREE.Vector2(),
            startScale: new THREE.Vector3(),
            initialScale: 1,
            isActive: false
        };
        
        function createScaleGizmo() {
            scaleGizmo.group = new THREE.Group();
            scaleGizmo.group.name = 'scaleGizmo';
            
            const arrowLength = 1.5;
            const coneRadius = 0.08;
            const coneHeight = 0.25;
            const lineWidth = 3;
            
            // Colors for each axis
            const colors = {
                x: 0xef4444, // Red
                y: 0x22c55e, // Green
                z: 0x3b82f6, // Blue
                uniform: 0xf59e0b // Yellow/Orange for center
            };
            
            // Create arrow for each axis
            function createArrow(axis, color) {
                const group = new THREE.Group();
                group.name = 'handle_' + axis;
                
                // Line
                const lineGeom = new THREE.BufferGeometry();
                const positions = axis === 'x' ? [0,0,0, arrowLength,0,0] :
                                  axis === 'y' ? [0,0,0, 0,arrowLength,0] :
                                                 [0,0,0, 0,0,arrowLength];
                lineGeom.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
                const lineMat = new THREE.LineBasicMaterial({ color: color, linewidth: lineWidth });
                const line = new THREE.Line(lineGeom, lineMat);
                group.add(line);
                
                // Arrow cone
                const coneGeom = new THREE.ConeGeometry(coneRadius, coneHeight, 12);
                const coneMat = new THREE.MeshBasicMaterial({ color: color });
                const cone = new THREE.Mesh(coneGeom, coneMat);
                
                if (axis === 'x') {
                    cone.rotation.z = -Math.PI / 2;
                    cone.position.set(arrowLength, 0, 0);
                } else if (axis === 'y') {
                    cone.position.set(0, arrowLength, 0);
                } else {
                    cone.rotation.x = Math.PI / 2;
                    cone.position.set(0, 0, arrowLength);
                }
                group.add(cone);
                
                // Invisible hit box for easier clicking
                const hitBoxGeom = new THREE.CylinderGeometry(0.12, 0.12, arrowLength, 8);
                const hitBoxMat = new THREE.MeshBasicMaterial({ visible: false });
                const hitBox = new THREE.Mesh(hitBoxGeom, hitBoxMat);
                hitBox.name = 'hitbox_' + axis;
                hitBox.userData.axis = axis;
                
                if (axis === 'x') {
                    hitBox.rotation.z = -Math.PI / 2;
                    hitBox.position.set(arrowLength / 2, 0, 0);
                } else if (axis === 'y') {
                    hitBox.position.set(0, arrowLength / 2, 0);
                } else {
                    hitBox.rotation.x = Math.PI / 2;
                    hitBox.position.set(0, 0, arrowLength / 2);
                }
                group.add(hitBox);
                
                return group;
            }
            
            // Create center cube for uniform scaling
            function createCenterCube() {
                const size = 0.2;
                const geom = new THREE.BoxGeometry(size, size, size);
                const mat = new THREE.MeshBasicMaterial({ color: colors.uniform });
                const cube = new THREE.Mesh(geom, mat);
                cube.name = 'hitbox_uniform';
                cube.userData.axis = 'uniform';
                return cube;
            }
            
            // Add arrows
            scaleGizmo.handles.x = createArrow('x', colors.x);
            scaleGizmo.handles.y = createArrow('y', colors.y);
            scaleGizmo.handles.z = createArrow('z', colors.z);
            scaleGizmo.handles.uniform = createCenterCube();
            
            scaleGizmo.group.add(scaleGizmo.handles.x);
            scaleGizmo.group.add(scaleGizmo.handles.y);
            scaleGizmo.group.add(scaleGizmo.handles.z);
            scaleGizmo.group.add(scaleGizmo.handles.uniform);
            
            scaleGizmo.group.visible = false;
            platformState.scene.add(scaleGizmo.group);
            
            console.log('📐 Scale gizmo created');
        }
        
        function showScaleGizmo() {
            if (!scaleGizmo.group) createScaleGizmo();
            if (!platformState.mesh) return;
            
            scaleGizmo.group.visible = true;
            scaleGizmo.group.position.copy(platformState.mesh.position);
            scaleGizmo.isActive = true;
            
            // Disable orbit controls when hovering gizmo
            setupGizmoInteraction();
        }
        
        function hideScaleGizmo() {
            if (scaleGizmo.group) {
                scaleGizmo.group.visible = false;
            }
            scaleGizmo.isActive = false;
            hideScalePopup();
        }
        
        // Scale popup functions
        const scalePopup = document.getElementById('scale-popup');
        const scalePopupLabel = document.getElementById('scale-popup-label');
        const scalePopupInput = document.getElementById('scale-popup-input');
        
        function showScalePopup(axis, x, y, value) {
            scalePopup.className = 'scale-input-popup active ' + axis + '-axis';
            scalePopup.style.left = (x + 20) + 'px';
            scalePopup.style.top = (y - 20) + 'px';
            
            const labels = { x: 'Scale X', y: 'Scale Y', z: 'Scale Z', uniform: 'Scale All' };
            scalePopupLabel.textContent = labels[axis];
            scalePopupInput.value = value.toFixed(2);
        }
        
        function hideScalePopup() {
            scalePopup.classList.remove('active');
        }
        
        function setupGizmoInteraction() {
            const canvas = platformState.renderer.domElement;
            const raycaster = new THREE.Raycaster();
            const mouse = new THREE.Vector2();
            let isDragging = false;
            let dragAxis = null;
            let startMouseX = 0;
            let startMouseY = 0;
            
            function getMousePos(e) {
                const rect = canvas.getBoundingClientRect();
                return {
                    x: e.clientX,
                    y: e.clientY,
                    normX: ((e.clientX - rect.left) / rect.width) * 2 - 1,
                    normY: -((e.clientY - rect.top) / rect.height) * 2 + 1
                };
            }
            
            function checkGizmoHit(normX, normY) {
                mouse.set(normX, normY);
                raycaster.setFromCamera(mouse, platformState.camera);
                
                const hitboxes = [];
                scaleGizmo.group.traverse(obj => {
                    if (obj.name && obj.name.startsWith('hitbox_')) {
                        hitboxes.push(obj);
                    }
                });
                
                const intersects = raycaster.intersectObjects(hitboxes);
                return intersects.length > 0 ? intersects[0].object.userData.axis : null;
            }
            
            function onMouseDown(e) {
                if (!scaleGizmo.isActive || e.button !== 0) return;
                
                const pos = getMousePos(e);
                const axis = checkGizmoHit(pos.normX, pos.normY);
                
                if (axis) {
                    isDragging = true;
                    dragAxis = axis;
                    startMouseX = pos.x;
                    startMouseY = pos.y;
                    scaleGizmo.startScale.copy(platformState.mesh.scale);
                    platformState.controls.enabled = false;
                    
                    const currentScale = axis === 'uniform' ? 
                        platformState.mesh.scale.x : 
                        platformState.mesh.scale[axis];
                    showScalePopup(axis, pos.x, pos.y, currentScale);
                    
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
            
            function onMouseMove(e) {
                const pos = getMousePos(e);
                
                if (isDragging && dragAxis) {
                    // Calculate scale change based on mouse movement
                    const deltaX = (pos.x - startMouseX) * 0.01;
                    const deltaY = -(pos.y - startMouseY) * 0.01;
                    const delta = dragAxis === 'y' ? deltaY : deltaX;
                    
                    let newScale;
                    if (dragAxis === 'uniform') {
                        newScale = Math.max(0.01, scaleGizmo.startScale.x + delta);
                        platformState.mesh.scale.setScalar(newScale);
                    } else {
                        newScale = Math.max(0.01, scaleGizmo.startScale[dragAxis] + delta);
                        platformState.mesh.scale[dragAxis] = newScale;
                    }
                    
                    // Update popup
                    showScalePopup(dragAxis, pos.x, pos.y, newScale);
                    
                    // Update right panel inputs
                    updateScaleInputs();
                } else if (scaleGizmo.isActive) {
                    // Hover effect
                    const axis = checkGizmoHit(pos.normX, pos.normY);
                    canvas.style.cursor = axis ? 'pointer' : 'default';
                }
            }
            
            function onMouseUp() {
                if (isDragging) {
                    isDragging = false;
                    dragAxis = null;
                    platformState.controls.enabled = true;
                    setTimeout(hideScalePopup, 1000);
                }
            }
            
            // Remove old listeners if any
            canvas.removeEventListener('mousedown', onMouseDown);
            canvas.removeEventListener('mousemove', onMouseMove);
            canvas.removeEventListener('mouseup', onMouseUp);
            
            // Add listeners
            canvas.addEventListener('mousedown', onMouseDown);
            canvas.addEventListener('mousemove', onMouseMove);
            canvas.addEventListener('mouseup', onMouseUp);
            window.addEventListener('mouseup', onMouseUp);
        }
        
        function updateScaleInputs() {
            if (!platformState.mesh) return;
            document.getElementById('scale-x').value = platformState.mesh.scale.x.toFixed(2);
            document.getElementById('scale-y').value = platformState.mesh.scale.y.toFixed(2);
            document.getElementById('scale-z').value = platformState.mesh.scale.z.toFixed(2);
        }
        
        // Handle popup input changes
        scalePopupInput.addEventListener('change', () => {
            if (!platformState.mesh || !scaleGizmo.activeHandle) return;
            
            const value = Math.max(0.01, parseFloat(scalePopupInput.value) || 1);
            const axis = scaleGizmo.activeHandle;
            
            if (axis === 'uniform') {
                platformState.mesh.scale.setScalar(value);
            } else {
                platformState.mesh.scale[axis] = value;
            }
            
            updateScaleInputs();
        });
        
        scalePopupInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                scalePopupInput.dispatchEvent(new Event('change'));
                hideScalePopup();
            } else if (e.key === 'Escape') {
                hideScalePopup();
            }
        });
        
        // ═══════════════════════════════════════════════════════════════
        // ROTATION GIZMO SYSTEM (Like Fusion 360)
        // ═══════════════════════════════════════════════════════════════
        
        const rotateGizmo = {
            group: null,
            rings: {},
            activeRing: null,
            startMouse: new THREE.Vector2(),
            startRotation: new THREE.Euler(),
            isActive: false
        };
        
        function createRotateGizmo() {
            rotateGizmo.group = new THREE.Group();
            rotateGizmo.group.name = 'rotateGizmo';
            
            const ringRadius = 1.8;
            const tubeRadius = 0.03;
            const segments = 64;
            
            // Colors for each axis
            const colors = {
                x: 0xef4444, // Red - rotation around X
                y: 0x22c55e, // Green - rotation around Y
                z: 0x3b82f6  // Blue - rotation around Z
            };
            
            // Create rotation ring for each axis
            function createRing(axis, color) {
                const group = new THREE.Group();
                group.name = 'ring_' + axis;
                
                // Visible ring (torus)
                const torusGeom = new THREE.TorusGeometry(ringRadius, tubeRadius, 16, segments);
                const torusMat = new THREE.MeshBasicMaterial({ color: color, transparent: true, opacity: 0.8 });
                const torus = new THREE.Mesh(torusGeom, torusMat);
                
                // Invisible hit ring (thicker for easier clicking)
                const hitGeom = new THREE.TorusGeometry(ringRadius, 0.15, 8, segments);
                const hitMat = new THREE.MeshBasicMaterial({ visible: false });
                const hitRing = new THREE.Mesh(hitGeom, hitMat);
                hitRing.name = 'hitring_' + axis;
                hitRing.userData.axis = axis;
                
                // Rotate rings to correct orientation
                if (axis === 'x') {
                    torus.rotation.y = Math.PI / 2;
                    hitRing.rotation.y = Math.PI / 2;
                } else if (axis === 'y') {
                    torus.rotation.x = Math.PI / 2;
                    hitRing.rotation.x = Math.PI / 2;
                }
                // Z axis ring is already in correct orientation
                
                group.add(torus);
                group.add(hitRing);
                
                // Add direction arrows on the ring
                const arrowCount = 4;
                for (let i = 0; i < arrowCount; i++) {
                    const angle = (i / arrowCount) * Math.PI * 2;
                    const arrowGeom = new THREE.ConeGeometry(0.08, 0.2, 8);
                    const arrowMat = new THREE.MeshBasicMaterial({ color: color });
                    const arrow = new THREE.Mesh(arrowGeom, arrowMat);
                    
                    if (axis === 'x') {
                        arrow.position.set(0, Math.cos(angle) * ringRadius, Math.sin(angle) * ringRadius);
                        arrow.rotation.x = angle + Math.PI / 2;
                    } else if (axis === 'y') {
                        arrow.position.set(Math.cos(angle) * ringRadius, 0, Math.sin(angle) * ringRadius);
                        arrow.rotation.z = -angle - Math.PI / 2;
                        arrow.rotation.order = 'ZXY';
                    } else {
                        arrow.position.set(Math.cos(angle) * ringRadius, Math.sin(angle) * ringRadius, 0);
                        arrow.rotation.z = angle + Math.PI / 2;
                    }
                    
                    group.add(arrow);
                }
                
                return group;
            }
            
            // Create rings
            rotateGizmo.rings.x = createRing('x', colors.x);
            rotateGizmo.rings.y = createRing('y', colors.y);
            rotateGizmo.rings.z = createRing('z', colors.z);
            
            rotateGizmo.group.add(rotateGizmo.rings.x);
            rotateGizmo.group.add(rotateGizmo.rings.y);
            rotateGizmo.group.add(rotateGizmo.rings.z);
            
            rotateGizmo.group.visible = false;
            platformState.scene.add(rotateGizmo.group);
            
            console.log('🔄 Rotation gizmo created');
        }
        
        function showRotateGizmo() {
            if (!rotateGizmo.group) createRotateGizmo();
            if (!platformState.mesh) return;
            
            rotateGizmo.group.visible = true;
            rotateGizmo.group.position.copy(platformState.mesh.position);
            rotateGizmo.isActive = true;
            
            setupRotateGizmoInteraction();
        }
        
        function hideRotateGizmo() {
            if (rotateGizmo.group) {
                rotateGizmo.group.visible = false;
            }
            rotateGizmo.isActive = false;
            hideRotatePopup();
        }
        
        // Rotation popup functions
        const rotatePopup = document.getElementById('rotate-popup');
        const rotatePopupLabel = document.getElementById('rotate-popup-label');
        const rotatePopupInput = document.getElementById('rotate-popup-input');
        
        function showRotatePopup(axis, x, y, degrees) {
            rotatePopup.className = 'rotate-input-popup active ' + axis + '-axis';
            rotatePopup.style.left = (x + 20) + 'px';
            rotatePopup.style.top = (y - 20) + 'px';
            
            const labels = { x: 'Rotate X', y: 'Rotate Y', z: 'Rotate Z', free: 'Free Rotate' };
            rotatePopupLabel.textContent = labels[axis];
            rotatePopupInput.value = Math.round(degrees);
        }
        
        function hideRotatePopup() {
            rotatePopup.classList.remove('active');
        }
        
        function setupRotateGizmoInteraction() {
            const canvas = platformState.renderer.domElement;
            const raycaster = new THREE.Raycaster();
            const mouse = new THREE.Vector2();
            let isDragging = false;
            let dragAxis = null;
            let startMouseX = 0;
            let startMouseY = 0;
            let startAngle = 0;
            
            function getMousePos(e) {
                const rect = canvas.getBoundingClientRect();
                return {
                    x: e.clientX,
                    y: e.clientY,
                    normX: ((e.clientX - rect.left) / rect.width) * 2 - 1,
                    normY: -((e.clientY - rect.top) / rect.height) * 2 + 1
                };
            }
            
            function checkGizmoHit(normX, normY) {
                mouse.set(normX, normY);
                raycaster.setFromCamera(mouse, platformState.camera);
                
                const hitRings = [];
                rotateGizmo.group.traverse(obj => {
                    if (obj.name && obj.name.startsWith('hitring_')) {
                        hitRings.push(obj);
                    }
                });
                
                const intersects = raycaster.intersectObjects(hitRings);
                return intersects.length > 0 ? intersects[0].object.userData.axis : null;
            }
            
            function radToDeg(rad) {
                return rad * (180 / Math.PI);
            }
            
            function degToRad(deg) {
                return deg * (Math.PI / 180);
            }
            
            function onMouseDown(e) {
                if (!rotateGizmo.isActive || e.button !== 0) return;
                
                const pos = getMousePos(e);
                const axis = checkGizmoHit(pos.normX, pos.normY);
                
                if (axis) {
                    isDragging = true;
                    dragAxis = axis;
                    startMouseX = pos.x;
                    startMouseY = pos.y;
                    rotateGizmo.startRotation.copy(platformState.mesh.rotation);
                    startAngle = radToDeg(platformState.mesh.rotation[axis]);
                    platformState.controls.enabled = false;
                    rotateGizmo.activeRing = axis;
                    
                    showRotatePopup(axis, pos.x, pos.y, startAngle);
                    
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
            
            function onMouseMove(e) {
                const pos = getMousePos(e);
                
                if (isDragging && dragAxis) {
                    // Calculate rotation based on mouse movement
                    const deltaX = pos.x - startMouseX;
                    const deltaY = pos.y - startMouseY;
                    
                    // Use different delta based on axis
                    let delta;
                    if (dragAxis === 'y') {
                        delta = deltaX * 0.5; // Horizontal movement for Y rotation
                    } else if (dragAxis === 'x') {
                        delta = deltaY * 0.5; // Vertical movement for X rotation
                    } else {
                        delta = deltaX * 0.5; // Horizontal for Z
                    }
                    
                    const newAngle = startAngle + delta;
                    platformState.mesh.rotation[dragAxis] = degToRad(newAngle);
                    
                    // Store rotation explicitly for export
                    if (!platformState.savedRotation) platformState.savedRotation = { x: 0, y: 0, z: 0 };
                    platformState.savedRotation[dragAxis] = degToRad(newAngle);
                    console.log('🔄 Rotation updated:', dragAxis, '=', newAngle, 'degrees');
                    
                    // Update popup
                    showRotatePopup(dragAxis, pos.x, pos.y, newAngle);
                    
                    // Update right panel inputs
                    updateRotationInputs();
                } else if (rotateGizmo.isActive) {
                    // Hover effect
                    const axis = checkGizmoHit(pos.normX, pos.normY);
                    canvas.style.cursor = axis ? 'grab' : 'default';
                    
                    // Highlight hovered ring
                    Object.keys(rotateGizmo.rings).forEach(key => {
                        const ring = rotateGizmo.rings[key];
                        ring.children.forEach(child => {
                            if (child.material && child.material.opacity !== undefined) {
                                child.material.opacity = key === axis ? 1 : 0.5;
                            }
                        });
                    });
                }
            }
            
            function onMouseUp() {
                if (isDragging) {
                    isDragging = false;
                    dragAxis = null;
                    platformState.controls.enabled = true;
                    setTimeout(hideRotatePopup, 1000);
                    
                    // Reset ring opacity
                    Object.values(rotateGizmo.rings).forEach(ring => {
                        ring.children.forEach(child => {
                            if (child.material && child.material.opacity !== undefined) {
                                child.material.opacity = 0.8;
                            }
                        });
                    });
                }
            }
            
            // Remove old listeners
            canvas.removeEventListener('mousedown', canvas._rotateDown);
            canvas.removeEventListener('mousemove', canvas._rotateMove);
            canvas.removeEventListener('mouseup', canvas._rotateUp);
            
            // Store references for cleanup
            canvas._rotateDown = onMouseDown;
            canvas._rotateMove = onMouseMove;
            canvas._rotateUp = onMouseUp;
            
            // Add listeners
            canvas.addEventListener('mousedown', onMouseDown);
            canvas.addEventListener('mousemove', onMouseMove);
            canvas.addEventListener('mouseup', onMouseUp);
            window.addEventListener('mouseup', onMouseUp);
        }
        
        function updateRotationInputs() {
            if (!platformState.mesh) return;
            const toDeg = (rad) => Math.round(rad * (180 / Math.PI));
            const rotX = document.getElementById('rotate-x');
            const rotY = document.getElementById('rotate-y');
            const rotZ = document.getElementById('rotate-z');
            if (rotX) rotX.value = toDeg(platformState.mesh.rotation.x);
            if (rotY) rotY.value = toDeg(platformState.mesh.rotation.y);
            if (rotZ) rotZ.value = toDeg(platformState.mesh.rotation.z);
        }
        
        // Handle rotation popup input changes
        rotatePopupInput.addEventListener('change', () => {
            if (!platformState.mesh || !rotateGizmo.activeRing) return;
            
            const degrees = parseFloat(rotatePopupInput.value) || 0;
            const axis = rotateGizmo.activeRing;
            platformState.mesh.rotation[axis] = degrees * (Math.PI / 180);
            
            updateRotationInputs();
        });
        
        rotatePopupInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                rotatePopupInput.dispatchEvent(new Event('change'));
                hideRotatePopup();
            } else if (e.key === 'Escape') {
                hideRotatePopup();
            }
        });
        
        // Update gizmo positions in animation loop
        function updateGizmoPositions() {
            if (platformState.mesh) {
                if (scaleGizmo.group && scaleGizmo.group.visible) {
                    scaleGizmo.group.position.copy(platformState.mesh.position);
                }
                if (rotateGizmo.group && rotateGizmo.group.visible) {
                    rotateGizmo.group.position.copy(platformState.mesh.position);
                }
            }
        }
        
        // ═══════════════════════════════════════════════════════════════
        // EVENT LISTENERS
        // ═══════════════════════════════════════════════════════════════
        
        // Track currently active tool
        let currentActiveTool = null;
        
        function setupEventListeners() {
            // Tool buttons - with Scale and Rotate gizmo toggle integration
            document.querySelectorAll('.tool-btn[data-tool]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const tool = btn.dataset.tool;
                    
                    // Check if clicking the same tool (toggle off)
                    if (currentActiveTool === tool) {
                        // Deselect - hide gizmo but keep modifications
                        btn.classList.remove('active');
                        hideScaleGizmo();
                        hideRotateGizmo();
                        currentActiveTool = null;
                        showToast('Tool deselected', 'info');
                        return;
                    }
                    
                    // Remove active from all tool buttons
                    document.querySelectorAll('.tool-btn[data-tool]').forEach(b => b.classList.remove('active'));
                    
                    // Hide all gizmos first
                    hideScaleGizmo();
                    hideRotateGizmo();
                    
                    // Show appropriate gizmo based on tool
                    if (tool === 'scale') {
                        btn.classList.add('active');
                        showScaleGizmo();
                        currentActiveTool = 'scale';
                        showToast('Scale tool active - drag arrows to scale. Click again to deselect.', 'info');
                    } else if (tool === 'rotate') {
                        btn.classList.add('active');
                        showRotateGizmo();
                        currentActiveTool = 'rotate';
                        showToast('Rotate tool active - drag rings to rotate. Click again to deselect.', 'info');
                    } else {
                        // Select tool (like Move or Select) but no gizmo
                        btn.classList.add('active');
                        currentActiveTool = tool;
                    }
                });
            });
            
            // Action buttons
            document.querySelectorAll('[data-action]').forEach(btn => {
                btn.addEventListener('click', () => handleAction(btn.dataset.action));
            });
            
            // Camera presets
            document.querySelectorAll('[data-camera]').forEach(btn => {
                btn.addEventListener('click', () => setCameraView(btn.dataset.camera));
            });
            
            // Panel toggles
            document.querySelectorAll('.panel-header').forEach(header => {
                header.addEventListener('click', () => {
                    header.parentElement.classList.toggle('collapsed');
                });
            });
            
            // Material color
            document.getElementById('material-color').addEventListener('input', (e) => {
                if (platformState.mesh) {
                    platformState.mesh.material.color.set(e.target.value);
                }
            });
            
            // Metalness
            document.getElementById('metalness').addEventListener('input', (e) => {
                if (platformState.mesh) {
                    platformState.mesh.material.metalness = parseFloat(e.target.value);
                }
            });
            
            // Roughness
            document.getElementById('roughness').addEventListener('input', (e) => {
                if (platformState.mesh) {
                    platformState.mesh.material.roughness = parseFloat(e.target.value);
                }
            });
            
            // Background color
            document.getElementById('bg-color').addEventListener('input', (e) => {
                platformState.scene.background.set(e.target.value);
            });
            
            // Grid opacity
            document.getElementById('grid-opacity').addEventListener('input', (e) => {
                platformState.gridHelper.material.opacity = parseFloat(e.target.value);
            });
            
            // Transform inputs
            ['pos-x', 'pos-y', 'pos-z'].forEach(id => {
                document.getElementById(id).addEventListener('input', updatePosition);
            });
            
            document.getElementById('scale-uniform').addEventListener('input', (e) => {
                if (platformState.mesh) {
                    const scale = parseFloat(e.target.value) || 1;
                    platformState.mesh.scale.setScalar(scale);
                }
            });
            
            // Export buttons
            document.querySelectorAll('[data-export]').forEach(btn => {
                btn.addEventListener('click', () => handleExport(btn.dataset.export));
            });
            
            // Reset Default button
            document.getElementById('reset-default-btn').addEventListener('click', resetToDefault);
        }
        
        // ═══════════════════════════════════════════════════════════════
        // RESET TO DEFAULT
        // ═══════════════════════════════════════════════════════════════
        
        const DEFAULT_VALUES = {
            position: { x: 0, y: 0, z: 0 },
            scale: 1,
            materialColor: '#6366f1',
            metalness: 0.3,
            roughness: 0.7,
            bgColor: '#1a1a2e',
            gridOpacity: 0.3,
            wireframe: false,
            gridVisible: true,
            cameraPosition: { x: 5, y: 5, z: 5 }
        };
        
        function resetToDefault() {
            if (!platformState.mesh) {
                showToast('No model to reset', 'warning');
                return;
            }
            
            // Reset mesh position
            platformState.mesh.position.set(0, 0, 0);
            document.getElementById('pos-x').value = 0;
            document.getElementById('pos-y').value = 0;
            document.getElementById('pos-z').value = 0;
            
            // Reset scale - reload geometry to get original scale
            loadGeometry();
            document.getElementById('scale-uniform').value = 1;
            
            // Reset material
            platformState.mesh.material.color.set(DEFAULT_VALUES.materialColor);
            platformState.mesh.material.metalness = DEFAULT_VALUES.metalness;
            platformState.mesh.material.roughness = DEFAULT_VALUES.roughness;
            platformState.mesh.material.wireframe = false;
            document.getElementById('material-color').value = DEFAULT_VALUES.materialColor;
            document.getElementById('metalness').value = DEFAULT_VALUES.metalness;
            document.getElementById('roughness').value = DEFAULT_VALUES.roughness;
            
            // Reset scene background
            platformState.scene.background.set(DEFAULT_VALUES.bgColor);
            document.getElementById('bg-color').value = DEFAULT_VALUES.bgColor;
            
            // Reset grid
            platformState.gridHelper.material.opacity = DEFAULT_VALUES.gridOpacity;
            platformState.gridHelper.visible = true;
            document.getElementById('grid-opacity').value = DEFAULT_VALUES.gridOpacity;
            
            // Reset wireframe state
            platformState.isWireframe = false;
            platformState.gridVisible = true;
            
            // Reset camera
            platformState.camera.position.set(
                DEFAULT_VALUES.cameraPosition.x,
                DEFAULT_VALUES.cameraPosition.y,
                DEFAULT_VALUES.cameraPosition.z
            );
            platformState.controls.target.set(0, 0, 0);
            platformState.controls.update();
            
            // Reset tool selection
            document.querySelectorAll('.tool-btn[data-tool]').forEach(b => b.classList.remove('active'));
            document.querySelector('.tool-btn[data-tool="select"]').classList.add('active');
            
            showToast('Reset to default values', 'success');
        }
        
        // ═══════════════════════════════════════════════════════════════
        // CLEAR PLATFORM
        // ═══════════════════════════════════════════════════════════════
        
        document.getElementById('clear-btn').addEventListener('click', clearPlatform);
        
        async function clearPlatform() {
            if (!platformState.mesh && !platformState.geometryData) {
                showToast('Platform is already empty', 'info');
                return;
            }
            
            // Remove mesh from scene
            if (platformState.mesh && platformState.scene) {
                platformState.scene.remove(platformState.mesh);
                if (platformState.mesh.geometry) platformState.mesh.geometry.dispose();
                if (platformState.mesh.material) platformState.mesh.material.dispose();
                platformState.mesh = null;
            }
            
            // Clear geometry data
            platformState.geometryData = null;
            platformState.fileType = null;
            
            // Clear IndexedDB storage (so refresh won't reload the model)
            try {
                await PlatformDB.clear();
                console.log('✓ IndexedDB cleared');
            } catch (err) {
                console.error('Failed to clear IndexedDB:', err);
            }
            
            // Hide gizmos
            hideScaleGizmo();
            hideRotateGizmo();
            currentActiveTool = null;
            
            // Reset UI
            document.getElementById('filename').textContent = 'No model loaded';
            document.getElementById('filetype-badge').textContent = '---';
            document.getElementById('vertex-count').textContent = 'Vertices: ---';
            document.getElementById('triangle-count').textContent = 'Triangles: ---';
            
            // Reset tool buttons
            document.querySelectorAll('.tool-btn[data-tool]').forEach(b => b.classList.remove('active'));
            
            // Hide all save/export buttons
            updateSaveButtonVisibility();
            
            showToast('Platform cleared completely', 'success');
        }
        
        // ═══════════════════════════════════════════════════════════════
        // SAVE BUTTON VISIBILITY CONTROL
        // ═══════════════════════════════════════════════════════════════
        
        function updateSaveButtonVisibility() {
            const saveBtn = document.getElementById('save-btn');
            const exportPanel = document.getElementById('export-panel');
            
            if (!platformState.geometryData || !platformState.mesh) {
                // No model loaded - hide everything
                saveBtn.style.display = 'none';
                exportPanel.style.display = 'none';
            } else if (platformState.fileType === 'HTML') {
                // HTML file loaded - show export panel with toggles
                saveBtn.style.display = 'none';
                exportPanel.style.display = 'flex';
            } else {
                // STL/OBJ/FBX/GLB file loaded - show Save HTML button
                saveBtn.style.display = 'inline-flex';
                exportPanel.style.display = 'none';
            }
        }
        
        // ═══════════════════════════════════════════════════════════════
        // UPLOAD FILE
        // ═══════════════════════════════════════════════════════════════
        
        const fileInput = document.getElementById('file-upload-input');
        
        document.getElementById('upload-btn').addEventListener('click', () => {
            fileInput.click();
        });
        
        fileInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            
            const fileName = file.name;
            const ext = fileName.split('.').pop().toLowerCase();
            
            showToast('Loading file: ' + fileName, 'info');
            
            try {
                if (ext === 'html' || ext === 'htm') {
                    // Load HTML file - extract geometry data
                    const text = await file.text();
                    console.log('📄 HTML file loaded, size:', text.length);
                    
                    // Try multiple patterns to find geometry data
                    let geometryMatch = text.match(/id="geometry-data"[^>]*>(\{[^<]+\})</);
                    if (!geometryMatch) {
                        geometryMatch = text.match(/id='geometry-data'[^>]*>(\{[^<]+\})</);
                    }
                    if (!geometryMatch) {
                        // Try to find JSON with vertices array
                        geometryMatch = text.match(/"geometry-data"[^>]*>([\s\S]*?)<\/script>/);
                    }
                    
                    console.log('📄 Geometry match found:', !!geometryMatch);
                    
                    if (geometryMatch) {
                        try {
                        const geometryData = JSON.parse(geometryMatch[1]);
                            console.log('📄 Parsed geometry:', geometryData.vertices?.length / 3, 'vertices');
                            
                        geometryData.name = fileName.replace(/\.[^.]+$/, '');
                            
                            // Initialize scene if not already done
                            if (!platformState.scene) {
                                console.log('🔧 Initializing scene for HTML import...');
                                document.getElementById('toolbar-left').style.display = '';
                                document.getElementById('viewport').style.display = 'block';
                                document.getElementById('panel-right').style.display = '';
                                document.getElementById('no-data').style.display = 'none';
                                
                                await new Promise(resolve => setTimeout(resolve, 100));
                                
                                if (typeof THREE !== 'undefined') {
                                    defineOrbitControls();
                                    setupScene();
                                    setupEventListeners();
                                    animate();
                                    console.log('✓ Scene and event listeners initialized for HTML import');
                                }
                            }
                        
                        platformState.geometryData = geometryData;
                        platformState.fileType = 'HTML';
                        
                        loadGeometry();
                        
                        // Apply saved settings if they exist
                        if (geometryData.settings && platformState.mesh) {
                            console.log('📄 Applying saved settings:', geometryData.settings);
                            
                            // Apply color
                            if (geometryData.settings.color) {
                                platformState.mesh.material.color.set(geometryData.settings.color);
                            }
                            
                            // Apply metalness and roughness
                            if (geometryData.settings.metalness !== undefined) {
                                platformState.mesh.material.metalness = geometryData.settings.metalness;
                            }
                            if (geometryData.settings.roughness !== undefined) {
                                platformState.mesh.material.roughness = geometryData.settings.roughness;
                            }
                            
                            // Apply position
                            if (geometryData.settings.position) {
                                platformState.mesh.position.set(
                                    geometryData.settings.position.x || 0,
                                    geometryData.settings.position.y || 0,
                                    geometryData.settings.position.z || 0
                                );
                                // Update UI
                                const posX = document.getElementById('pos-x');
                                const posY = document.getElementById('pos-y');
                                const posZ = document.getElementById('pos-z');
                                if (posX) posX.value = geometryData.settings.position.x || 0;
                                if (posY) posY.value = geometryData.settings.position.y || 0;
                                if (posZ) posZ.value = geometryData.settings.position.z || 0;
                            }
                            
                            // Apply rotation
                            if (geometryData.settings.rotation) {
                                const rx = geometryData.settings.rotation.x || 0;
                                const ry = geometryData.settings.rotation.y || 0;
                                const rz = geometryData.settings.rotation.z || 0;
                                
                                console.log('📐 Setting rotation to:', rx, ry, rz);
                                console.log('📐 In degrees:', rx * 180/Math.PI, ry * 180/Math.PI, rz * 180/Math.PI);
                                
                                platformState.mesh.rotation.set(rx, ry, rz);
                                
                                // Also store in savedRotation for re-export
                                platformState.savedRotation = { x: rx, y: ry, z: rz };
                                
                                console.log('📐 Mesh rotation after set:', 
                                    platformState.mesh.rotation.x,
                                    platformState.mesh.rotation.y,
                                    platformState.mesh.rotation.z
                                );
                            }
                            
                            // Apply scale
                            console.log('📐 Scale - loadGeometry set:', platformState.mesh.scale.x);
                            console.log('📐 Scale - saved value:', geometryData.settings.scale);
                            if (geometryData.settings.scale !== undefined) {
                                platformState.mesh.scale.setScalar(geometryData.settings.scale);
                                const scaleInput = document.getElementById('scale-uniform');
                                if (scaleInput) scaleInput.value = geometryData.settings.scale;
                                console.log('📐 Scale - applied:', geometryData.settings.scale);
                            } else {
                                console.log('📐 Scale - NOT applying (undefined in settings)');
                            }
                            
                            // Apply background color
                            if (geometryData.settings.background && platformState.scene) {
                                platformState.scene.background = new THREE.Color(geometryData.settings.background);
                            }
                            
                            console.log('✓ All settings applied successfully');
                        }
                        
                        document.getElementById('filename').textContent = geometryData.name;
                        document.getElementById('filetype-badge').textContent = 'HTML';
                        
                        updateSaveButtonVisibility();
                            showToast('HTML model loaded: ' + (geometryData.vertices?.length / 3 || 0) + ' vertices', 'success');
                        } catch (parseErr) {
                            console.error('JSON parse error:', parseErr);
                            showToast('Failed to parse geometry data from HTML', 'error');
                        }
                    } else {
                        console.log('📄 No geometry-data found in HTML');
                        showToast('No geometry data found in HTML file. Make sure it was exported from Fusion Viewer.', 'error');
                    }
                } else if (ext === 'stl' || ext === 'obj' || ext === 'fbx' || ext === 'glb' || ext === 'gltf') {
                    // Load 3D model file
                    console.log('📁 Reading file:', file.name, 'Type:', file.type, 'Size:', file.size);
                    const arrayBuffer = await file.arrayBuffer();
                    console.log('📁 ArrayBuffer obtained, size:', arrayBuffer.byteLength);
                    
                    // Debug: show first 20 bytes as text
                    const firstBytes = new Uint8Array(arrayBuffer, 0, Math.min(20, arrayBuffer.byteLength));
                    const asText = String.fromCharCode(...firstBytes);
                    console.log('📁 First 20 bytes as text:', asText);
                    
                    await load3DFile(arrayBuffer, ext, fileName);
                } else {
                    showToast('Unsupported file format: ' + ext + '. Supported: STL, OBJ, FBX, GLB, GLTF', 'error');
                }
            } catch (error) {
                console.error('Upload error:', error);
                showToast('Failed to load file: ' + error.message, 'error');
            }
            
            // Reset file input
            fileInput.value = '';
        });
        
        async function load3DFile(arrayBuffer, ext, fileName) {
            // Initialize scene if not already done (for direct file uploads)
            if (!platformState.scene) {
                console.log('🔧 Initializing scene for direct upload...');
                
                // Wait for THREE.js to be available
                if (typeof THREE === 'undefined') {
                    showToast('3D library not loaded. Please refresh the page.', 'error');
                    return;
                }
                
                // Show the viewport and hide no-data message
                document.getElementById('toolbar-left').style.display = '';
                document.getElementById('viewport').style.display = 'block';
                document.getElementById('panel-right').style.display = '';
                document.getElementById('no-data').style.display = 'none';
                
                // Wait for DOM to recalculate layout (longer wait)
                await new Promise(resolve => setTimeout(resolve, 100));
                
                try {
                    defineOrbitControls();
                    setupScene();
                    setupEventListeners();  // Add event listeners for export buttons
                    animate();
                    
                    // Verify scene was created
                    if (!platformState.scene) {
                        throw new Error('Scene failed to initialize');
                    }
                    console.log('✓ Scene initialized for direct upload');
                    console.log('✓ Event listeners attached');
                } catch (initError) {
                    console.error('Scene initialization error:', initError);
                    showToast('Failed to initialize 3D viewer: ' + initError.message, 'error');
                    return;
                }
            }
            
            // For STL files, parse directly
            if (ext === 'stl') {
                try {
                    console.log('📂 Parsing STL file:', fileName, 'Size:', arrayBuffer.byteLength);
                    const vertices = parseSTL(arrayBuffer);
                    console.log('📐 Parsed vertices:', vertices.length / 3);
                    
                    if (vertices && vertices.length > 0) {
                        // Clear existing model first
                        if (platformState.mesh && platformState.scene) {
                            platformState.scene.remove(platformState.mesh);
                            if (platformState.mesh.geometry) platformState.mesh.geometry.dispose();
                            if (platformState.mesh.material) platformState.mesh.material.dispose();
                        }
                        
                        platformState.geometryData = {
                            vertices: vertices,
                            name: fileName.replace(/\.[^.]+$/, '')
                        };
                        platformState.fileType = 'STL';
                        
                        // Verify scene exists before loading geometry
                        if (!platformState.scene) {
                            console.error('Scene is null before loadGeometry');
                            showToast('3D scene not ready. Please try again.', 'error');
                            return;
                        }
                        
                        loadGeometry();
                        
                        document.getElementById('filename').textContent = platformState.geometryData.name;
                        document.getElementById('filetype-badge').textContent = 'STL';
                        
                        updateSaveButtonVisibility();
                        showToast('STL model loaded: ' + (vertices.length / 3) + ' vertices', 'success');
                    } else {
                        console.error('No vertices parsed from STL');
                        showToast('Failed to parse STL file - no vertices found', 'error');
                    }
                } catch (err) {
                    console.error('STL parse error:', err);
                    showToast('STL parse error: ' + err.message, 'error');
                }
            } else if (ext === 'obj') {
                // Parse OBJ file directly
                try {
                    console.log('📂 Parsing OBJ file:', fileName, 'Size:', arrayBuffer.byteLength);
                    const objText = new TextDecoder().decode(arrayBuffer);
                    const vertices = parseOBJ(objText);
                    console.log('📐 Parsed vertices:', vertices.length / 3);
                    
                    if (vertices && vertices.length > 0) {
                        // Clear existing model first
                        if (platformState.mesh && platformState.scene) {
                            platformState.scene.remove(platformState.mesh);
                            if (platformState.mesh.geometry) platformState.mesh.geometry.dispose();
                            if (platformState.mesh.material) platformState.mesh.material.dispose();
                        }
                        
                        platformState.geometryData = {
                            vertices: vertices,
                            name: fileName.replace(/\.[^.]+$/, '')
                        };
                        platformState.fileType = 'OBJ';
                        
                        // Verify scene exists before loading geometry
                        if (!platformState.scene) {
                            console.error('Scene is null before loadGeometry');
                            showToast('3D scene not ready. Please try again.', 'error');
                            return;
                        }
                        
                        loadGeometry();
                        
                        document.getElementById('filename').textContent = platformState.geometryData.name;
                        document.getElementById('filetype-badge').textContent = 'OBJ';
                        
                        updateSaveButtonVisibility();
                        showToast('OBJ model loaded: ' + (vertices.length / 3) + ' vertices', 'success');
            } else {
                        console.error('No vertices parsed from OBJ');
                        showToast('Failed to parse OBJ file - no vertices found', 'error');
                    }
                } catch (err) {
                    console.error('OBJ parse error:', err);
                    showToast('OBJ parse error: ' + err.message, 'error');
                }
            } else if (ext === 'fbx') {
                // Parse FBX file using FBXLoader
                try {
                    console.log('📂 Parsing FBX file:', fileName, 'Size:', arrayBuffer.byteLength);
                    
                    // Wait for FBXLoader to be ready
                    if (!window.FBXLoader) {
                        // Wait a bit for the module to load
                        await new Promise(resolve => {
                            const checkLoader = setInterval(() => {
                                if (window.FBXLoader || window.fbxLoaderReady) {
                                    clearInterval(checkLoader);
                                    resolve();
                                }
                            }, 100);
                            // Timeout after 5 seconds
                            setTimeout(() => {
                                clearInterval(checkLoader);
                                resolve();
                            }, 5000);
                        });
                    }
                    
                    if (!window.FBXLoader) {
                        throw new Error('FBXLoader not available. Please refresh the page.');
                    }
                    
                    const fbxLoader = new window.FBXLoader();
                    const blob = new Blob([arrayBuffer]);
                    const url = URL.createObjectURL(blob);
                    
                    fbxLoader.load(url, (fbxObject) => {
                        console.log('📐 FBX loaded:', fbxObject);
                        URL.revokeObjectURL(url);
                        
                        // Extract vertices AND material color from FBX
                        const vertices = [];
                        let extractedColor = null;
                        let extractedMetalness = 0.3;
                        let extractedRoughness = 0.7;
                        
                        fbxObject.traverse((child) => {
                            // Check for any mesh type (Mesh, SkinnedMesh, etc.)
                            if ((child.isMesh || child.isSkinnedMesh) && child.geometry) {
                                console.log('Found mesh:', child.name, child.type);
                                const geometry = child.geometry;
                                const positions = geometry.attributes.position;
                                
                                // Extract color from first mesh's material
                                if (!extractedColor && child.material) {
                                    const mat = Array.isArray(child.material) ? child.material[0] : child.material;
                                    if (mat && mat.color) {
                                        extractedColor = '#' + mat.color.getHexString();
                                        console.log('📦 Extracted color from FBX:', extractedColor);
                                    }
                                    if (mat && mat.metalness !== undefined) {
                                        extractedMetalness = mat.metalness;
                                    }
                                    if (mat && mat.roughness !== undefined) {
                                        extractedRoughness = mat.roughness;
                                    }
                                }
                                
                                if (positions) {
                                    // Apply world matrix to get correct positions
                                    child.updateMatrixWorld(true);
                                    const matrix = child.matrixWorld;
                                    
                                    // Check if geometry is indexed
                                    if (geometry.index) {
                                        // Indexed geometry - use indices to build triangles
                                        const indices = geometry.index;
                                        console.log('Indexed geometry:', indices.count, 'indices');
                                        
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
                                        // Non-indexed geometry
                                        console.log('Non-indexed geometry:', positions.count, 'vertices');
                                        
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
                        
                        console.log('📐 Total extracted vertices:', vertices.length / 3);
                        
                        // Debug: log object structure
                        console.log('🔍 FBX object structure:');
                        let meshCount = 0;
                        fbxObject.traverse((child) => {
                            console.log('  -', child.type, child.name || '(unnamed)', 
                                child.geometry ? 'has geometry' : 'no geometry');
                            if (child.geometry) meshCount++;
                        });
                        console.log('Total meshes with geometry:', meshCount);
                        
                        if (vertices.length > 0) {
                            // Clear existing model first
                            if (platformState.mesh && platformState.scene) {
                                platformState.scene.remove(platformState.mesh);
                                if (platformState.mesh.geometry) platformState.mesh.geometry.dispose();
                                if (platformState.mesh.material) platformState.mesh.material.dispose();
                            }
                            
                            platformState.geometryData = {
                                vertices: vertices,
                                name: fileName.replace(/\.[^.]+$/, ''),
                                originalColor: extractedColor,
                                originalMetalness: extractedMetalness,
                                originalRoughness: extractedRoughness
                            };
                            platformState.fileType = 'FBX';
                            
                            loadGeometry();
                            
                            // Apply extracted color from FBX
                            if (extractedColor && platformState.mesh && platformState.mesh.material) {
                                platformState.mesh.material.color.set(extractedColor);
                                platformState.mesh.material.metalness = extractedMetalness;
                                platformState.mesh.material.roughness = extractedRoughness;
                                console.log('✓ Applied original FBX color:', extractedColor);
                            }
                            
                            document.getElementById('filename').textContent = platformState.geometryData.name;
                            document.getElementById('filetype-badge').textContent = 'FBX';
                            
                            updateSaveButtonVisibility();
                            showToast('FBX model loaded: ' + (vertices.length / 3) + ' vertices', 'success');
                        } else {
                            // Fallback: Add FBX object directly to scene for viewing
                            console.log('⚠️ No vertices extracted, adding FBX directly to scene');
                            
                            // Clear existing model
                            if (platformState.mesh && platformState.scene) {
                                platformState.scene.remove(platformState.mesh);
                            }
                            
                            // Scale and center the FBX model
                            const box = new THREE.Box3().setFromObject(fbxObject);
                            const center = box.getCenter(new THREE.Vector3());
                            const size = box.getSize(new THREE.Vector3());
                            const maxDim = Math.max(size.x, size.y, size.z);
                            const scale = 3 / maxDim;
                            
                            fbxObject.position.sub(center);
                            fbxObject.scale.setScalar(scale);
                            
                            // Apply a uniform material to all meshes
                            fbxObject.traverse((child) => {
                                if (child.isMesh || child.isSkinnedMesh) {
                                    child.material = new THREE.MeshStandardMaterial({
                                        color: 0x6366f1,
                                        metalness: 0.3,
                                        roughness: 0.7,
                                        side: THREE.DoubleSide
                                    });
                                }
                            });
                            
                            platformState.scene.add(fbxObject);
                            platformState.mesh = fbxObject;
                            platformState.fileType = 'FBX';
                            platformState.geometryData = { 
                                name: fileName.replace(/\.[^.]+$/, ''),
                                vertices: [],
                                isFbxObject: true
                            };
                            
                            document.getElementById('filename').textContent = platformState.geometryData.name;
                            document.getElementById('filetype-badge').textContent = 'FBX';
                            
                            // Update vertex count from meshes
                            let totalVerts = 0;
                            fbxObject.traverse((child) => {
                                if (child.geometry && child.geometry.attributes.position) {
                                    totalVerts += child.geometry.attributes.position.count;
                                }
                            });
                            document.getElementById('vertex-count').textContent = 'Vertices: ' + totalVerts.toLocaleString();
                            document.getElementById('triangle-count').textContent = 'Triangles: ' + Math.floor(totalVerts / 3).toLocaleString();
                            
                            showToast('FBX model loaded (view mode): ' + totalVerts.toLocaleString() + ' vertices', 'success');
                        }
                    }, 
                    (progress) => {
                        // Progress callback
                        if (progress.total > 0) {
                            const percent = Math.round((progress.loaded / progress.total) * 100);
                            console.log('FBX loading progress:', percent + '%');
                        }
                    },
                    (error) => {
                        console.error('FBX load error:', error);
                        URL.revokeObjectURL(url);
                        
                        // Check for specific error types
                        if (error.message && error.message.includes('version not supported')) {
                            // Try fallback parser for FBX 6.x
                            console.log('⚠️ Attempting FBX 6.x fallback parser...');
                            showToast('Trying legacy FBX parser...', 'info');
                            
                            // Extract version number
                            const versionMatch = error.message.match(/FileVersion:\s*(\d+)/);
                            const version = versionMatch ? versionMatch[1] : '6.x';
                            const yearEstimate = version === '6100' ? '2006' : (version === '6000' ? '2005' : '2006 or earlier');
                            
                            // Show professional modal
                            showFBXVersionModal(version, yearEstimate);
                            return;
                        } else {
                            showToast('FBX load error: ' + error.message, 'error');
                        }
                    });
                    
                } catch (err) {
                    console.error('FBX parse error:', err);
                    showToast('FBX parse error: ' + err.message, 'error');
                }
            } else if (ext === 'glb' || ext === 'gltf') {
                // Parse GLB/GLTF file using GLTFLoader
                try {
                    console.log('📂 Parsing GLB/GLTF file:', fileName, 'Size:', arrayBuffer.byteLength);
                    
                    // Wait for GLTFLoader to be ready
                    if (!window.GLTFLoader) {
                        await new Promise(resolve => {
                            const checkLoader = setInterval(() => {
                                if (window.GLTFLoader || window.gltfLoaderReady) {
                                    clearInterval(checkLoader);
                                    resolve();
                                }
                            }, 100);
                            setTimeout(() => {
                                clearInterval(checkLoader);
                                resolve();
                            }, 5000);
                        });
                    }
                    
                    if (!window.GLTFLoader) {
                        throw new Error('GLTFLoader not available. Please refresh the page.');
                    }
                    
                    console.log('GLTFLoader available:', !!window.GLTFLoader);
                    console.log('ArrayBuffer size:', arrayBuffer.byteLength);
                    
                    // Check first few bytes to verify it's a GLB file
                    const header = new Uint8Array(arrayBuffer, 0, 4);
                    const magic = String.fromCharCode(...header);
                    console.log('File magic:', magic, '(should be "glTF" for GLB)');
                    
                    const gltfLoader = new window.GLTFLoader();
                    
                    // Parse the buffer directly using parse method
                    // Signature: parse(data, path, onLoad, onError)
                    gltfLoader.parse(
                        arrayBuffer, 
                        '', 
                        (gltf) => {
                            console.log('📐 GLTF loaded successfully:', gltf);
                            
                            const gltfScene = gltf.scene;
                        
                        // Extract vertices AND material color from GLTF
                        const vertices = [];
                        let extractedColor = null;
                        let extractedMetalness = 0.3;
                        let extractedRoughness = 0.7;
                        
                        gltfScene.traverse((child) => {
                            if (child.isMesh && child.geometry) {
                                const geometry = child.geometry;
                                const positions = geometry.attributes.position;
                                
                                // Extract color from first mesh's material
                                if (!extractedColor && child.material) {
                                    if (child.material.color) {
                                        extractedColor = '#' + child.material.color.getHexString();
                                        console.log('📦 Extracted color from GLB:', extractedColor);
                                    }
                                    if (child.material.metalness !== undefined) {
                                        extractedMetalness = child.material.metalness;
                                    }
                                    if (child.material.roughness !== undefined) {
                                        extractedRoughness = child.material.roughness;
                                    }
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
                        
                        console.log('📐 Extracted vertices:', vertices.length / 3);
                        
                        if (vertices.length > 0) {
                            // Clear existing model
                            if (platformState.mesh && platformState.scene) {
                                platformState.scene.remove(platformState.mesh);
                                if (platformState.mesh.geometry) platformState.mesh.geometry.dispose();
                                if (platformState.mesh.material) platformState.mesh.material.dispose();
                            }
                            
                            platformState.geometryData = {
                                vertices: vertices,
                                name: fileName.replace(/\.[^.]+$/, ''),
                                originalColor: extractedColor,
                                originalMetalness: extractedMetalness,
                                originalRoughness: extractedRoughness
                            };
                            platformState.fileType = ext.toUpperCase();
                            
                            loadGeometry();
                            
                            // Apply extracted color from GLB/GLTF
                            if (extractedColor && platformState.mesh && platformState.mesh.material) {
                                platformState.mesh.material.color.set(extractedColor);
                                platformState.mesh.material.metalness = extractedMetalness;
                                platformState.mesh.material.roughness = extractedRoughness;
                                console.log('✓ Applied original GLB color:', extractedColor);
                            }
                            
                            document.getElementById('filename').textContent = platformState.geometryData.name;
                            document.getElementById('filetype-badge').textContent = ext.toUpperCase();
                            
                            updateSaveButtonVisibility();
                            showToast(ext.toUpperCase() + ' model loaded: ' + (vertices.length / 3) + ' vertices', 'success');
                        } else {
                            // Fallback: Add GLTF scene directly
                            console.log('⚠️ No vertices extracted, adding GLTF directly to scene');
                            
                            if (platformState.mesh && platformState.scene) {
                                platformState.scene.remove(platformState.mesh);
                            }
                            
                            // Scale and center
                            const box = new THREE.Box3().setFromObject(gltfScene);
                            const center = box.getCenter(new THREE.Vector3());
                            const size = box.getSize(new THREE.Vector3());
                            const maxDim = Math.max(size.x, size.y, size.z);
                            const scale = 3 / maxDim;
                            
                            gltfScene.position.sub(center);
                            gltfScene.scale.setScalar(scale);
                            
                            platformState.scene.add(gltfScene);
                            platformState.mesh = gltfScene;
                            platformState.fileType = ext.toUpperCase();
                            platformState.geometryData = {
                                name: fileName.replace(/\.[^.]+$/, ''),
                                vertices: [],
                                isGltfObject: true
                            };
                            
                            document.getElementById('filename').textContent = platformState.geometryData.name;
                            document.getElementById('filetype-badge').textContent = ext.toUpperCase();
                            
                            let totalVerts = 0;
                            gltfScene.traverse((child) => {
                                if (child.geometry && child.geometry.attributes.position) {
                                    totalVerts += child.geometry.attributes.position.count;
                                }
                            });
                            document.getElementById('vertex-count').textContent = 'Vertices: ' + totalVerts.toLocaleString();
                            document.getElementById('triangle-count').textContent = 'Triangles: ' + Math.floor(totalVerts / 3).toLocaleString();
                            
                            showToast(ext.toUpperCase() + ' model loaded (view mode): ' + totalVerts.toLocaleString() + ' vertices', 'success');
                        }
                    },
                    (error) => {
                        console.error('GLTF load error:', error);
                        showToast('GLB/GLTF load error: ' + (error.message || error), 'error');
                    });
                    
                } catch (err) {
                    console.error('GLTF parse error:', err);
                    showToast('GLB/GLTF parse error: ' + err.message, 'error');
                }
            } else {
                showToast('Unsupported format: ' + ext, 'error');
            }
        }
        
        // Robust STL parser (supports both ASCII and Binary)
        function parseSTL(buffer) {
            const vertices = [];
            
            // First, try to detect if it's ASCII by checking for "solid" keyword
            const headerBytes = new Uint8Array(buffer, 0, Math.min(80, buffer.byteLength));
            const headerText = new TextDecoder().decode(headerBytes).trim().toLowerCase();
            
            // Check if starts with "solid" (ASCII STL indicator)
            // But also check it's not a binary file with "solid" in header
            const startsWithSolid = headerText.startsWith('solid');
            
            // For binary detection: check if file size matches expected binary size
            const dataView = new DataView(buffer);
            let isBinary = false;
            
            if (buffer.byteLength > 84) {
                const numTriangles = dataView.getUint32(80, true);
                const expectedSize = 84 + (numTriangles * 50); // 50 bytes per triangle
                // If size matches binary format (with some tolerance), it's binary
                if (Math.abs(buffer.byteLength - expectedSize) < 100) {
                    isBinary = true;
                }
            }
            
            // If it doesn't start with "solid" or size matches binary format, treat as binary
            if (!startsWithSolid || isBinary) {
                console.log('📄 Parsing as Binary STL');
                // Binary STL
                try {
                    const numTriangles = dataView.getUint32(80, true);
                    console.log('📐 Number of triangles:', numTriangles);
                    
                    if (numTriangles > 0 && numTriangles < 50000000) { // Sanity check
                        let offset = 84;
                        
                        for (let i = 0; i < numTriangles; i++) {
                            // Skip normal vector (12 bytes)
                            offset += 12;
                            
                            // Read 3 vertices
                            for (let j = 0; j < 3; j++) {
                                const x = dataView.getFloat32(offset, true);
                                const y = dataView.getFloat32(offset + 4, true);
                                const z = dataView.getFloat32(offset + 8, true);
                                vertices.push(x, y, z);
                                offset += 12;
                            }
                            
                            // Skip attribute byte count (2 bytes)
                            offset += 2;
                        }
                    }
                } catch (e) {
                    console.error('Binary STL parse error:', e);
                }
            }
            
            // If binary parsing failed or got no vertices, try ASCII
            if (vertices.length === 0) {
                console.log('📄 Parsing as ASCII STL');
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
                    console.log('📐 ASCII vertices found:', vertices.length / 3);
                } catch (e) {
                    console.error('ASCII STL parse error:', e);
                }
            }
            
            return vertices;
        }
        
        // FBX 6.x Parser - attempts to extract geometry from legacy FBX files
        function parseFBX6(buffer) {
            const vertices = [];
            let debug = '';
            
            // FBX 6.x can be ASCII or binary - try ASCII first
            let text = '';
            try {
                text = new TextDecoder('utf-8', { fatal: false }).decode(buffer);
            } catch (e) {
                debug += 'Could not decode as text. ';
            }
            
            const fileSize = buffer.byteLength;
            const first100 = text.substring(0, 100).replace(/[^\x20-\x7E]/g, '.');
            debug += 'Size: ' + fileSize + ' bytes. ';
            debug += 'Header: ' + first100.substring(0, 50) + '... ';
            
            console.log('File size:', fileSize, 'bytes');
            console.log('First 200 chars:', text.substring(0, 200));
            
            // Check if it's ASCII FBX (look for various markers)
            const isAscii = text.includes('FBXHeaderExtension') || 
                           text.includes('Vertices:') || 
                           text.includes('Model:') ||
                           text.includes('Objects:') ||
                           text.includes('; FBX');
            
            const isBinary = first100.includes('Kaydara FBX Binary');
            debug += isBinary ? 'Type: Binary FBX. ' : (isAscii ? 'Type: ASCII FBX. ' : 'Type: Unknown. ');
            
            if (isAscii) {
                console.log('Detected ASCII FBX 6.x');
                debug += 'Parsing ASCII... ';
                
                // Find Vertices arrays in the file
                // FBX 6.x format: Vertices: *count { a: x,y,z,x,y,z,... }
                const verticesRegex = /Vertices:\s*\*(\d+)\s*\{\s*a:\s*([\d\s,.\-e]+)/gi;
                let match;
                
                while ((match = verticesRegex.exec(text)) !== null) {
                    const vertexData = match[2];
                    const numbers = vertexData.split(',').map(s => parseFloat(s.trim())).filter(n => !isNaN(n));
                    
                    // Add vertices (x, y, z triplets)
                    for (let i = 0; i < numbers.length; i++) {
                        vertices.push(numbers[i]);
                    }
                }
                
                // If no vertices found with that pattern, try alternative patterns
                if (vertices.length === 0) {
                    console.log('Trying alternative vertex patterns...');
                    
                    // Pattern 2: Vertices: x,y,z,x,y,z
                    const altRegex = /Vertices:\s*([\d\s,.\-e]+)/gi;
                    while ((match = altRegex.exec(text)) !== null) {
                        const numbers = match[1].split(',').map(s => parseFloat(s.trim())).filter(n => !isNaN(n));
                        for (let i = 0; i < numbers.length; i++) {
                            vertices.push(numbers[i]);
                        }
                    }
                }
                
                // Pattern 3: Look for coordinate data in different format
                if (vertices.length === 0) {
                    console.log('Trying coordinate scan...');
                    
                    // Some FBX files have vertex data like: v: x, y, z
                    const coordRegex = /[-]?\d+\.\d+,\s*[-]?\d+\.\d+,\s*[-]?\d+\.\d+/g;
                    const matches = text.match(coordRegex);
                    if (matches && matches.length > 10) {
                        console.log('Found', matches.length, 'coordinate triplets');
                        for (const m of matches) {
                            const nums = m.split(',').map(s => parseFloat(s.trim()));
                            if (nums.length === 3 && nums.every(n => !isNaN(n))) {
                                vertices.push(...nums);
                            }
                        }
                    }
                }
                
                console.log('ASCII parsing found', vertices.length / 3, 'vertices');
                
                // Find PolygonVertexIndex for face indices
                const indicesRegex = /PolygonVertexIndex:\s*\*(\d+)\s*\{\s*a:\s*([\d\s,.\-]+)/gi;
                const allIndices = [];
                
                while ((match = indicesRegex.exec(text)) !== null) {
                    const indexData = match[2];
                    const indices = indexData.split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n));
                    allIndices.push(...indices);
                }
                
                // If we have both vertices and indices, reconstruct triangles
                if (vertices.length > 0 && allIndices.length > 0) {
                    console.log('Found', vertices.length / 3, 'raw vertices and', allIndices.length, 'indices');
                    
                    // Store raw vertices
                    const rawVertices = [];
                    for (let i = 0; i < vertices.length; i += 3) {
                        rawVertices.push([vertices[i], vertices[i + 1], vertices[i + 2]]);
                    }
                    
                    // Build triangulated faces from indices
                    // FBX uses negative index to mark end of polygon
                    const triangulatedVerts = [];
                    let faceVerts = [];
                    
                    for (const idx of allIndices) {
                        let vertIdx = idx;
                        let isEndOfFace = false;
                        
                        if (idx < 0) {
                            vertIdx = -idx - 1; // Convert negative index
                            isEndOfFace = true;
                        }
                        
                        if (vertIdx < rawVertices.length) {
                            faceVerts.push(rawVertices[vertIdx]);
                        }
                        
                        if (isEndOfFace && faceVerts.length >= 3) {
                            // Triangulate the face (fan triangulation)
                            for (let i = 1; i < faceVerts.length - 1; i++) {
                                triangulatedVerts.push(...faceVerts[0]);
                                triangulatedVerts.push(...faceVerts[i]);
                                triangulatedVerts.push(...faceVerts[i + 1]);
                            }
                            faceVerts = [];
                        }
                    }
                    
                    return triangulatedVerts;
                }
                
                // Return raw vertices if no indices found
                if (vertices.length > 0) {
                    return { vertices: vertices, debug: debug + 'Found ' + (vertices.length/3) + ' vertices.' };
                }
                
                debug += 'No vertices found in ASCII. ';
            }
            
            // Try binary FBX 6.x parsing
            console.log('Attempting binary FBX 6.x parse...');
            
            const dataView = new DataView(buffer);
            const headerText = new TextDecoder().decode(buffer.slice(0, 23));
            
            if (headerText.includes('Kaydara FBX Binary')) {
                console.log('Binary FBX detected, searching for geometry data...');
                
                // Binary FBX structure: look for vertex data patterns
                // Vertices are typically stored as consecutive float32 values
                const floats = [];
                
                // Scan for "Vertices" string in binary
                const uint8 = new Uint8Array(buffer);
                const searchStr = 'Vertices';
                let verticesOffset = -1;
                
                for (let i = 0; i < uint8.length - searchStr.length; i++) {
                    let found = true;
                    for (let j = 0; j < searchStr.length; j++) {
                        if (uint8[i + j] !== searchStr.charCodeAt(j)) {
                            found = false;
                            break;
                        }
                    }
                    if (found) {
                        verticesOffset = i;
                        console.log('Found "Vertices" at offset:', verticesOffset);
                        break;
                    }
                }
                
                if (verticesOffset > 0) {
                    // Look for vertex count and data after the Vertices marker
                    // Skip past the property name and look for float array
                    let offset = verticesOffset + 20; // Skip past header
                    
                    // Try to find array of doubles (FBX 6.x often uses doubles)
                    // Look for a reasonable vertex count marker
                    for (let searchOffset = verticesOffset; searchOffset < Math.min(verticesOffset + 200, buffer.byteLength - 8); searchOffset++) {
                        // Check if this could be a count followed by doubles
                        try {
                            const possibleCount = dataView.getUint32(searchOffset, true);
                            if (possibleCount > 9 && possibleCount < 1000000 && possibleCount % 3 === 0) {
                                // Might be vertex count, try reading doubles after
                                const dataStart = searchOffset + 4;
                                const testVerts = [];
                                let valid = true;
                                
                                for (let i = 0; i < Math.min(possibleCount, 30); i++) {
                                    const val = dataView.getFloat64(dataStart + i * 8, true);
                                    if (isNaN(val) || !isFinite(val) || Math.abs(val) > 100000) {
                                        valid = false;
                                        break;
                                    }
                                    testVerts.push(val);
                                }
                                
                                if (valid && testVerts.length >= 9) {
                                    console.log('Found potential vertex data at offset:', dataStart);
                                    // Read all vertices
                                    for (let i = 0; i < possibleCount; i++) {
                                        const val = dataView.getFloat64(dataStart + i * 8, true);
                                        if (!isNaN(val) && isFinite(val)) {
                                            vertices.push(val);
                                        }
                                    }
                                    if (vertices.length > 0) {
                                        console.log('Extracted', vertices.length / 3, 'vertices from binary FBX');
                                        break;
                                    }
                                }
                            }
                        } catch (e) {
                            continue;
                        }
                    }
                }
                
                // If still no vertices, try scanning for float32 patterns
                if (vertices.length === 0) {
                    console.log('Scanning for float32 vertex patterns...');
                    
                    // Look for "Geometry" section
                    let geometryOffset = -1;
                    const geoStr = 'Geometry';
                    for (let i = 0; i < uint8.length - geoStr.length; i++) {
                        let found = true;
                        for (let j = 0; j < geoStr.length; j++) {
                            if (uint8[i + j] !== geoStr.charCodeAt(j)) {
                                found = false;
                                break;
                            }
                        }
                        if (found) {
                            geometryOffset = i;
                            console.log('Found "Geometry" at offset:', geometryOffset);
                            break;
                        }
                    }
                    
                    // Scan from geometry section for float arrays
                    const startScan = geometryOffset > 0 ? geometryOffset : 100;
                    for (let i = startScan; i < buffer.byteLength - 12; i += 4) {
                        try {
                            const f1 = dataView.getFloat32(i, true);
                            const f2 = dataView.getFloat32(i + 4, true);
                            const f3 = dataView.getFloat32(i + 8, true);
                            
                            // Check if these look like valid 3D coordinates
                            if (Math.abs(f1) < 10000 && Math.abs(f2) < 10000 && Math.abs(f3) < 10000 &&
                                Math.abs(f1) > 0.0001 && (Math.abs(f2) > 0.0001 || Math.abs(f3) > 0.0001)) {
                                
                                // Found potential vertex, try to read more
                                const tempVerts = [f1, f2, f3];
                                let validCount = 1;
                                
                                for (let j = 1; j < 1000; j++) {
                                    const offset = i + j * 12;
                                    if (offset + 12 > buffer.byteLength) break;
                                    
                                    const v1 = dataView.getFloat32(offset, true);
                                    const v2 = dataView.getFloat32(offset + 4, true);
                                    const v3 = dataView.getFloat32(offset + 8, true);
                                    
                                    if (Math.abs(v1) < 10000 && Math.abs(v2) < 10000 && Math.abs(v3) < 10000) {
                                        tempVerts.push(v1, v2, v3);
                                        validCount++;
                                    } else {
                                        break;
                                    }
                                }
                                
                                if (validCount > 100) {
                                    console.log('Found', validCount, 'vertices starting at offset', i);
                                    vertices.push(...tempVerts);
                                    break;
                                }
                            }
                        } catch (e) {
                            continue;
                        }
                    }
                }
                
                if (vertices.length > 0) {
                    debug += 'Found ' + (vertices.length/3) + ' vertices in binary.';
                } else {
                    debug += 'No vertices found in binary data.';
                }
            }
            
            return { vertices: vertices, debug: debug };
        }
        
        // OBJ Parser - parses OBJ text format into flat vertex array
        function parseOBJ(text) {
            const vertices = [];
            const positions = [];  // Store v (vertex positions)
            const lines = text.split('\n');
            
            // First pass: collect all vertex positions
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
                    // Parse face indices (OBJ uses 1-based indexing)
                    // Format can be: f v1 v2 v3 or f v1/vt1/vn1 v2/vt2/vn2 ...
                    const faceIndices = [];
                    for (let i = 1; i < parts.length; i++) {
                        // Extract vertex index (first number before any /)
                        const idx = parseInt(parts[i].split('/')[0]) - 1;
                        if (idx >= 0 && idx < positions.length) {
                            faceIndices.push(idx);
                        }
                    }
                    
                    // Triangulate face (fan triangulation for polygons)
                    for (let i = 1; i < faceIndices.length - 1; i++) {
                        // Triangle: vertex 0, vertex i, vertex i+1
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
            
            console.log('OBJ: Generated', vertices.length / 3, 'vertices from triangulated faces');
            return vertices;
        }
        
        // ═══════════════════════════════════════════════════════════════
        // EXPORT MODAL & SELECTED FORMATS (for HTML files)
        // ═══════════════════════════════════════════════════════════════
        
        const exportModal = document.getElementById('export-modal');
        const exportFilenameInput = document.getElementById('export-filename');
        const exportFormatsDisplay = document.getElementById('export-formats-display');
        const exportModalConfirm = document.getElementById('export-modal-confirm');
        
        let pendingExportFormats = { stl: false, obj: false, glb: false, fbx: false };
        
        // Show export modal
        document.getElementById('export-selected-btn').addEventListener('click', () => {
            const exportSTL = document.getElementById('export-stl').checked;
            const exportOBJ = document.getElementById('export-obj').checked;
            const exportGLB = document.getElementById('export-glb').checked;
            
            if (!exportSTL && !exportOBJ && !exportGLB) {
                showToast('Please select at least one format to export', 'warning');
                return;
            }
            
            if (!platformState.mesh) {
                showToast('No model to export', 'error');
                return;
            }
            
            // Store pending formats (FBX not available - requires external tools)
            pendingExportFormats = { stl: exportSTL, obj: exportOBJ, glb: exportGLB, fbx: false };
            
            // Set default filename from original
            const originalName = platformState.geometryData?.name || 'model';
            exportFilenameInput.value = originalName;
            exportFilenameInput.placeholder = originalName;
            
            // Display selected formats
            let formatsHTML = '<div class="format-label">Will export as:</div><div class="format-badges">';
            if (exportSTL) formatsHTML += '<div class="format-badge"><span>📦</span> STL</div>';
            if (exportOBJ) formatsHTML += '<div class="format-badge"><span>📦</span> OBJ</div>';
            if (exportGLB) formatsHTML += '<div class="format-badge"><span>📦</span> GLB</div>';
            formatsHTML += '</div>';
            exportFormatsDisplay.innerHTML = formatsHTML;
            
            // Show modal
            exportModal.classList.add('active');
            exportFilenameInput.focus();
            exportFilenameInput.select();
        });
        
        // Close export modal
        function closeExportModal() {
            exportModal.classList.remove('active');
        }
        
        document.getElementById('export-modal-close').addEventListener('click', closeExportModal);
        document.getElementById('export-modal-cancel').addEventListener('click', closeExportModal);
        
        exportModal.addEventListener('click', (e) => {
            if (e.target === exportModal) closeExportModal();
        });
        
        // Confirm export
        exportModalConfirm.addEventListener('click', async () => {
            const filename = exportFilenameInput.value.trim() || platformState.geometryData?.name || 'model';
            
            exportModalConfirm.disabled = true;
            exportModalConfirm.innerHTML = '<span>⏳</span> Exporting...';
            
            let exportCount = 0;
            
            try {
                // Get transformed geometry
                const mesh = platformState.mesh;
                mesh.updateMatrixWorld(true);
                const positions = mesh.geometry.attributes.position;
                const matrix = new THREE.Matrix4();
                matrix.compose(mesh.position, mesh.quaternion, mesh.scale);
                
                // Extract transformed vertices
                const vertices = [];
                for (let i = 0; i < positions.count; i++) {
                    const vertex = new THREE.Vector3(
                        positions.getX(i),
                        positions.getY(i),
                        positions.getZ(i)
                    );
                    vertex.applyMatrix4(matrix);
                    vertices.push(vertex);
                }
                
                // Export STL
                if (pendingExportFormats.stl) {
                    const stlContent = generateSTL(vertices, filename);
                    downloadFile(stlContent, filename + '.stl', 'application/octet-stream');
                    exportCount++;
                }
                
                // Export OBJ
                if (pendingExportFormats.obj) {
                    const objContent = generateOBJ(vertices, filename);
                    downloadFile(objContent, filename + '.obj', 'text/plain');
                    exportCount++;
                }
                
                // Export GLB
                if (pendingExportFormats.glb) {
                    await exportToGLB(filename);
                    exportCount++;
                }
                
                // Export FBX (as OBJ with .fbx note - true FBX requires Autodesk SDK)
                if (pendingExportFormats.fbx) {
                    const objContent = generateOBJ(vertices, filename);
                    downloadFile(objContent, filename + '_fbx.obj', 'text/plain');
                    exportCount++;
                }
                
                closeExportModal();
                showToast(`Exported ${exportCount} file(s): ${filename}`, 'success');
                
            } catch (error) {
                console.error('Export error:', error);
                showToast('Export failed: ' + error.message, 'error');
            }
            
            exportModalConfirm.disabled = false;
            exportModalConfirm.innerHTML = '<span>⬇️</span> Export';
        });
        
        // Handle Enter key in filename input
        exportFilenameInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                exportModalConfirm.click();
            } else if (e.key === 'Escape') {
                closeExportModal();
            }
        });
        
        function generateSTL(vertices, name) {
            // Use binary STL for large models (more efficient, smaller file size)
            const triangleCount = Math.floor(vertices.length / 3);
            console.log('📦 Generating binary STL with', triangleCount, 'triangles');
            
            // Binary STL format:
            // - 80 bytes header
            // - 4 bytes triangle count (uint32)
            // - For each triangle: 12 bytes normal + 36 bytes vertices + 2 bytes attribute = 50 bytes
            const bufferSize = 80 + 4 + (triangleCount * 50);
            const buffer = new ArrayBuffer(bufferSize);
            const dataView = new DataView(buffer);
            const uint8 = new Uint8Array(buffer);
            
            // Write header (80 bytes)
            const header = 'Binary STL - Fusion Platform - ' + name.substring(0, 40);
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
                const len = Math.sqrt(nx*nx + ny*ny + nz*nz) || 1;
                
                // Write normal (3 x float32)
                dataView.setFloat32(offset, nx/len, true); offset += 4;
                dataView.setFloat32(offset, ny/len, true); offset += 4;
                dataView.setFloat32(offset, nz/len, true); offset += 4;
                
                // Write vertices (9 x float32)
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
            
            console.log('📦 Binary STL generated:', (buffer.byteLength / 1024 / 1024).toFixed(2), 'MB');
            return buffer;
        }
        
        function generateOBJ(vertices, name, includeMTL = false) {
            let obj = '# OBJ file exported from Fusion Platform\n';
            obj += '# Object: ' + name + '\n\n';
            
            // Reference MTL file if included
            if (includeMTL) {
                obj += 'mtllib ' + name + '.mtl\n\n';
            }
            
            // Write vertices
            for (const v of vertices) {
                obj += 'v ' + v.x.toFixed(6) + ' ' + v.y.toFixed(6) + ' ' + v.z.toFixed(6) + '\n';
            }
            
            obj += '\n';
            
            // Use material
            if (includeMTL) {
                obj += 'usemtl FusionMaterial\n';
            }
            
            obj += '# Faces\n';
            
            // Write faces (triangles)
            for (let i = 0; i < vertices.length; i += 3) {
                const idx = i + 1; // OBJ uses 1-based indexing
                obj += 'f ' + idx + ' ' + (idx + 1) + ' ' + (idx + 2) + '\n';
            }
            
            return obj;
        }
        
        // Generate MTL (material) file for OBJ
        function generateMTL(name, color, metalness, roughness) {
            const r = color.r.toFixed(6);
            const g = color.g.toFixed(6);
            const b = color.b.toFixed(6);
            
            // Convert metalness/roughness to OBJ material properties
            // Ns = specular exponent (0-1000), higher = more shiny
            const specular = Math.round((1 - roughness) * 200);
            // d = dissolve/opacity (1 = opaque)
            
            let mtl = '# MTL file exported from Fusion Platform\n';
            mtl += '# Material for: ' + name + '\n\n';
            mtl += 'newmtl FusionMaterial\n';
            mtl += 'Ka ' + (parseFloat(r) * 0.2).toFixed(6) + ' ' + (parseFloat(g) * 0.2).toFixed(6) + ' ' + (parseFloat(b) * 0.2).toFixed(6) + '\n'; // Ambient
            mtl += 'Kd ' + r + ' ' + g + ' ' + b + '\n'; // Diffuse (main color)
            mtl += 'Ks 0.500000 0.500000 0.500000\n'; // Specular
            mtl += 'Ns ' + specular + '\n'; // Specular exponent
            mtl += 'd 1.000000\n'; // Opacity
            mtl += 'illum 2\n'; // Illumination model
            
            return mtl;
        }
        
        function downloadFile(content, filename, mimeType) {
            const blob = new Blob([content], { type: mimeType });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
        
        function handleAction(action) {
            switch (action) {
                case 'reset-view':
                    platformState.camera.position.set(5, 5, 5);
                    platformState.controls.target.set(0, 0, 0);
                    platformState.controls.update();
                    break;
                case 'zoom-fit':
                    if (platformState.mesh) {
                        const box = new THREE.Box3().setFromObject(platformState.mesh);
                        const size = box.getSize(new THREE.Vector3());
                        const maxDim = Math.max(size.x, size.y, size.z);
                        platformState.camera.position.set(maxDim * 2, maxDim * 2, maxDim * 2);
                        platformState.controls.update();
                    }
                    break;
                case 'wireframe':
                    if (platformState.mesh) {
                        platformState.isWireframe = !platformState.isWireframe;
                        platformState.mesh.material.wireframe = platformState.isWireframe;
                    }
                    break;
                case 'grid-toggle':
                    platformState.gridVisible = !platformState.gridVisible;
                    platformState.gridHelper.visible = platformState.gridVisible;
                    break;
                case 'fullscreen':
                    if (document.fullscreenElement) {
                        document.exitFullscreen();
                    } else {
                        document.getElementById('viewport').requestFullscreen();
                    }
                    break;
            }
        }
        
        function setCameraView(view) {
            const dist = 8;
            const positions = {
                front: [0, 0, dist],
                back: [0, 0, -dist],
                left: [-dist, 0, 0],
                right: [dist, 0, 0],
                top: [0, dist, 0.01],
                perspective: [dist/2, dist/2, dist/2]
            };
            
            if (positions[view]) {
                platformState.camera.position.set(...positions[view]);
                platformState.controls.target.set(0, 0, 0);
                platformState.controls.update();
            }
        }
        
        function updatePosition() {
            if (platformState.mesh) {
                const x = parseFloat(document.getElementById('pos-x').value) || 0;
                const y = parseFloat(document.getElementById('pos-y').value) || 0;
                const z = parseFloat(document.getElementById('pos-z').value) || 0;
                platformState.mesh.position.set(x, y, z);
            }
        }
        
        async function handleExport(format) {
            if (!platformState.mesh || !platformState.geometryData) {
                showToast('No model loaded to export', 'error');
                return;
            }
            
            // Force update mesh matrix to ensure rotation is current
            platformState.mesh.updateMatrixWorld(true);
            
            // Debug: Log current mesh state
            console.log('🔍 EXPORT DEBUG:');
            console.log('   Mesh rotation (radians):', platformState.mesh.rotation.x, platformState.mesh.rotation.y, platformState.mesh.rotation.z);
            console.log('   Mesh rotation (degrees):', 
                (platformState.mesh.rotation.x * 180 / Math.PI).toFixed(1),
                (platformState.mesh.rotation.y * 180 / Math.PI).toFixed(1),
                (platformState.mesh.rotation.z * 180 / Math.PI).toFixed(1)
            );
            console.log('   Mesh quaternion:', platformState.mesh.quaternion.x, platformState.mesh.quaternion.y, platformState.mesh.quaternion.z, platformState.mesh.quaternion.w);
            
            const filename = platformState.geometryData.name || 'model';
            showToast(`Exporting as ${format.toUpperCase()}...`, 'info');
            
            try {
                // Get transformed vertices
                const vertices = getTransformedVertices();
                
                switch (format.toLowerCase()) {
                    case 'html':
                        // Export as standalone HTML viewer
                        const currentColor = platformState.mesh.material.color.getHexString();
                        const currentMetalness = platformState.mesh.material.metalness || 0.3;
                        const currentRoughness = platformState.mesh.material.roughness || 0.7;
                        
                        // Use ORIGINAL vertices (without transforms) so we can apply transforms separately
                        let exportVertices = [];
                        
                        if (platformState.geometryData && platformState.geometryData.vertices) {
                            // Use original geometry data
                            exportVertices = platformState.geometryData.vertices.slice();
                            console.log('📐 Using original geometry data for HTML export');
                        } else if (platformState.mesh.geometry && platformState.mesh.geometry.attributes.position) {
                            // Fallback: extract from mesh geometry (without transforms)
                            const positions = platformState.mesh.geometry.attributes.position;
                            for (let i = 0; i < positions.count; i++) {
                                exportVertices.push(
                                    positions.getX(i),
                                    positions.getY(i),
                                    positions.getZ(i)
                                );
                            }
                        }
                        
                        if (exportVertices.length === 0) {
                            showToast('No vertex data to export', 'error');
                            break;
                        }
                        
                        console.log('📐 Exporting HTML with', exportVertices.length / 3, 'vertices, color:', currentColor);
                        console.log('📐 Rotation:', platformState.mesh.rotation.x, platformState.mesh.rotation.y, platformState.mesh.rotation.z);
                        
                        // Store ALL settings for re-import (transforms will be applied on import)
                        const transformedGeometryData = {
                            vertices: exportVertices,
                            name: platformState.geometryData.name || filename,
                            settings: {
                                color: '#' + currentColor,
                                metalness: currentMetalness,
                                roughness: currentRoughness,
                                position: {
                                    x: platformState.mesh.position.x,
                                    y: platformState.mesh.position.y,
                                    z: platformState.mesh.position.z
                                },
                                rotation: {
                                    x: platformState.mesh.rotation.x,
                                    y: platformState.mesh.rotation.y,
                                    z: platformState.mesh.rotation.z
                                },
                                scale: platformState.mesh.scale.x,
                                background: platformState.scene.background ? '#' + platformState.scene.background.getHexString() : '#1a1a2e'
                            }
                        };
                        
                        const htmlContent = generateHTMLViewer(transformedGeometryData, filename, {
                            color: '#' + currentColor,
                            metalness: currentMetalness,
                            roughness: currentRoughness,
                            background: platformState.scene.background ? '#' + platformState.scene.background.getHexString() : '#1a1a2e'
                        });
                        downloadFile(htmlContent, filename + '.html', 'text/html');
                        showToast('HTML Viewer exported successfully!', 'success');
                        break;
                        
                    case 'stl':
                        const stlContent = generateSTL(vertices, filename);
                        downloadFile(stlContent, filename + '.stl', 'application/octet-stream');
                        showToast('STL file exported successfully!', 'success');
                        break;
                        
                    case 'obj':
                        // Get current material color
                        const objColor = platformState.mesh.material ? 
                            platformState.mesh.material.color : new THREE.Color(0x6366f1);
                        const objMetalness = platformState.mesh.material?.metalness || 0.3;
                        const objRoughness = platformState.mesh.material?.roughness || 0.7;
                        
                        // Generate OBJ with MTL reference
                        const objContent = generateOBJ(vertices, filename, true);
                        
                        // Generate MTL file with material properties
                        const mtlContent = generateMTL(filename, objColor, objMetalness, objRoughness);
                        
                        // Download both files
                        downloadFile(objContent, filename + '.obj', 'text/plain');
                        downloadFile(mtlContent, filename + '.mtl', 'text/plain');
                        
                        showToast('OBJ + MTL files exported (color preserved)!', 'success');
                        break;
                        
                    case 'glb':
                        await exportToGLB(filename);
                        break;
                        
                    default:
                        showToast('Unknown export format: ' + format, 'error');
                }
            } catch (error) {
                console.error('Export error:', error);
                showToast('Export failed: ' + error.message, 'error');
            }
        }
        
        // GLB Export using Three.js GLTFExporter
        async function exportToGLB(filename) {
            return new Promise((resolve, reject) => {
                if (!platformState.mesh) {
                    showToast('No model to export', 'error');
                    reject(new Error('No model'));
                    return;
                }
                
                // Create a GLTFExporter
                const exporter = new THREE.GLTFExporter();
                
                // Clone the mesh for export and ensure material is preserved
                const exportMesh = platformState.mesh.clone();
                
                // Ensure material color is explicitly set (deep clone may not preserve it)
                if (exportMesh.material && platformState.mesh.material) {
                    exportMesh.material = platformState.mesh.material.clone();
                    console.log('📦 GLB Export - Material color:', '#' + exportMesh.material.color.getHexString());
                }
                
                exporter.parse(
                    exportMesh,
                    (result) => {
                        // Result is an ArrayBuffer for binary GLB
                        const blob = new Blob([result], { type: 'application/octet-stream' });
                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = filename + '.glb';
                        link.click();
                        URL.revokeObjectURL(url);
                        showToast('GLB file exported successfully!', 'success');
                        resolve(true);
                    },
                    (error) => {
                        console.error('GLB export error:', error);
                        showToast('GLB export failed: ' + error, 'error');
                        reject(error);
                    },
                    { binary: true }
                );
            });
        }
        
        // Get transformed vertices from the current mesh (handles single mesh and groups)
        function getTransformedVertices() {
            const vertices = [];
            if (!platformState.mesh) {
                console.warn('📐 getTransformedVertices: No mesh!');
                return vertices;
            }
            
            console.log('📐 getTransformedVertices: mesh exists, geometry:', !!platformState.mesh.geometry);
            
            // Check if it's a group/object with children (FBX/GLTF fallback mode)
            if (platformState.geometryData?.isFbxObject || platformState.geometryData?.isGltfObject || !platformState.mesh.geometry) {
                console.log('📐 Extracting vertices from group/object...');
                
                // Traverse all child meshes
                platformState.mesh.traverse((child) => {
                    if (child.isMesh && child.geometry && child.geometry.attributes.position) {
                        const positions = child.geometry.attributes.position;
                        child.updateMatrixWorld(true);
                        const matrix = child.matrixWorld;
                        
                        // Handle indexed geometry
                        if (child.geometry.index) {
                            const indices = child.geometry.index;
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
                    }
                });
                
                console.log('📐 Extracted', vertices.length, 'vertices from group');
            } else {
                // Single mesh with geometry
                const geometry = platformState.mesh.geometry;
                const positions = geometry.attributes.position;
                
                console.log('📐 Single mesh, positions count:', positions ? positions.count : 'NONE');
                
                if (!positions) {
                    console.error('📐 No position attribute in geometry!');
                    return vertices;
                }
                
                platformState.mesh.updateMatrixWorld(true);
                const matrix = platformState.mesh.matrixWorld;
                
                // Handle indexed geometry
                if (geometry.index) {
                    const indices = geometry.index;
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
                
                console.log('📐 Extracted', vertices.length, 'vertices from single mesh');
            }
            
            return vertices;
        }
        
        // ═══════════════════════════════════════════════════════════════
        // ORBIT CONTROLS (Inline)
        // ═══════════════════════════════════════════════════════════════
        
        function defineOrbitControls() {
            if (window.OrbitControls) return;
            const _changeEvent = { type: "change" };
            const _startEvent = { type: "start" };
            const _endEvent = { type: "end" };
            class OrbitControls extends THREE.EventDispatcher {
                constructor(object, domElement) {
                    super();
                    this.object = object;
                    this.domElement = domElement;
                    this.domElement.style.touchAction = "none";
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
                        if (scope.enableDamping) scope.target.addScaledVector(panOffset, scope.dampingFactor);
                        else scope.target.add(panOffset);
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
                        if (lastPosition.distanceToSquared(scope.object.position) > EPS || 8 * (1 - lastQuaternion.dot(scope.object.quaternion)) > EPS) {
                            scope.dispatchEvent(_changeEvent);
                            lastPosition.copy(scope.object.position);
                            lastQuaternion.copy(scope.object.quaternion);
                        }
                        return false;
                    };
                    function rotateLeft(angle) { sphericalDelta.theta -= angle; }
                    function rotateUp(angle) { sphericalDelta.phi -= angle; }
                    const v = new THREE.Vector3();
                    function panLeft(distance, objectMatrix) { v.setFromMatrixColumn(objectMatrix, 0); v.multiplyScalar(-distance); panOffset.add(v); }
                    function panUp(distance, objectMatrix) { v.setFromMatrixColumn(objectMatrix, 1); v.multiplyScalar(distance); panOffset.add(v); }
                    function pan(deltaX, deltaY) {
                        const element = scope.domElement;
                        const position = scope.object.position;
                        offset.copy(position).sub(scope.target);
                        let targetDistance = offset.length();
                        targetDistance *= Math.tan((scope.object.fov / 2) * Math.PI / 180.0);
                        panLeft(2 * deltaX * targetDistance / element.clientHeight, scope.object.matrix);
                        panUp(2 * deltaY * targetDistance / element.clientHeight, scope.object.matrix);
                    }
                    function dollyOut(dollyScale) { scale /= dollyScale; }
                    function dollyIn(dollyScale) { scale *= dollyScale; }
                    function getZoomScale() { return Math.pow(0.95, scope.zoomSpeed); }
                    function onPointerDown(event) {
                        if (!scope.enabled) return;
                        if (event.button === 0) { state = STATE.ROTATE; rotateStart.set(event.clientX, event.clientY); }
                        else if (event.button === 1) { state = STATE.DOLLY; dollyStart.set(event.clientX, event.clientY); }
                        else if (event.button === 2) { state = STATE.PAN; panStart.set(event.clientX, event.clientY); }
                        if (state !== STATE.NONE) { document.addEventListener("pointermove", onPointerMove); document.addEventListener("pointerup", onPointerUp); }
                    }
                    function onPointerMove(event) {
                        if (!scope.enabled) return;
                        if (state === STATE.ROTATE) { rotateEnd.set(event.clientX, event.clientY); rotateDelta.subVectors(rotateEnd, rotateStart).multiplyScalar(scope.rotateSpeed); rotateLeft(2 * Math.PI * rotateDelta.x / scope.domElement.clientHeight); rotateUp(2 * Math.PI * rotateDelta.y / scope.domElement.clientHeight); rotateStart.copy(rotateEnd); }
                        else if (state === STATE.DOLLY) { dollyEnd.set(event.clientX, event.clientY); dollyDelta.subVectors(dollyEnd, dollyStart); if (dollyDelta.y > 0) dollyOut(getZoomScale()); else if (dollyDelta.y < 0) dollyIn(getZoomScale()); dollyStart.copy(dollyEnd); }
                        else if (state === STATE.PAN) { panEnd.set(event.clientX, event.clientY); panDelta.subVectors(panEnd, panStart).multiplyScalar(scope.panSpeed); pan(panDelta.x, panDelta.y); panStart.copy(panEnd); }
                        scope.update();
                    }
                    function onPointerUp() { document.removeEventListener("pointermove", onPointerMove); document.removeEventListener("pointerup", onPointerUp); state = STATE.NONE; }
                    function onMouseWheel(event) { if (!scope.enabled || !scope.enableZoom) return; event.preventDefault(); if (event.deltaY < 0) dollyIn(getZoomScale()); else if (event.deltaY > 0) dollyOut(getZoomScale()); scope.update(); }
                    function onContextMenu(event) { if (scope.enabled) event.preventDefault(); }
                    scope.domElement.addEventListener("contextmenu", onContextMenu);
                    scope.domElement.addEventListener("pointerdown", onPointerDown);
                    scope.domElement.addEventListener("wheel", onMouseWheel, { passive: false });
                    this.update();
                }
            }
            window.OrbitControls = OrbitControls;
        }
        
        // ═══════════════════════════════════════════════════════════════
        // TOAST NOTIFICATIONS
        // ═══════════════════════════════════════════════════════════════
        
        // Professional FBX Version Error Modal
        function showFBXVersionModal(version, yearEstimate) {
            // Remove existing modal if any
            const existingModal = document.getElementById('fbx-version-modal');
            if (existingModal) existingModal.remove();
            
            const modal = document.createElement('div');
            modal.id = 'fbx-version-modal';
            modal.innerHTML = `
                <div style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0,0,0,0.7);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    backdrop-filter: blur(4px);
                ">
                    <div style="
                        background: linear-gradient(135deg, #1e1e2e 0%, #2d2d44 100%);
                        border-radius: 16px;
                        padding: 32px;
                        max-width: 480px;
                        width: 90%;
                        box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(99,102,241,0.2);
                        color: #fff;
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    ">
                        <div style="text-align: center; margin-bottom: 24px;">
                            <div style="
                                width: 64px;
                                height: 64px;
                                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                margin: 0 auto 16px;
                                font-size: 28px;
                            ">⚠️</div>
                            <h2 style="margin: 0 0 8px; font-size: 22px; font-weight: 600;">Unsupported FBX Version</h2>
                            <p style="margin: 0; color: #a0a0b0; font-size: 14px;">Legacy format detected</p>
                        </div>
                        
                        <div style="
                            background: rgba(0,0,0,0.2);
                            border-radius: 12px;
                            padding: 16px;
                            margin-bottom: 24px;
                        ">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="color: #a0a0b0;">File Version</span>
                                <span style="color: #f59e0b; font-weight: 600;">FBX ${version}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="color: #a0a0b0;">Format Year</span>
                                <span style="color: #a0a0b0;">~${yearEstimate}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #a0a0b0;">Required Version</span>
                                <span style="color: #22c55e; font-weight: 600;">FBX 7.x (2011+)</span>
                            </div>
                        </div>
                        
                        <p style="
                            color: #c0c0d0;
                            font-size: 14px;
                            line-height: 1.6;
                            margin: 0 0 24px;
                            text-align: center;
                        ">
                            This file uses a legacy FBX format that is no longer supported by modern 3D viewers. 
                            Please convert your file to a newer format.
                        </p>
                        
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <a href="https://www.greentoken.de/onlineconv/" target="_blank" style="
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                gap: 8px;
                                background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
                                color: white;
                                padding: 14px 24px;
                                border-radius: 10px;
                                text-decoration: none;
                                font-weight: 600;
                                font-size: 15px;
                                transition: transform 0.2s, box-shadow 0.2s;
                            " onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(99,102,241,0.4)'" 
                               onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                                🔄 Convert Online (Free)
                            </a>
                            
                            <div style="display: flex; gap: 12px;">
                                <button onclick="document.getElementById('fbx-version-modal').remove()" style="
                                    flex: 1;
                                    background: rgba(255,255,255,0.1);
                                    color: #c0c0d0;
                                    padding: 12px 20px;
                                    border-radius: 10px;
                                    border: 1px solid rgba(255,255,255,0.1);
                                    cursor: pointer;
                                    font-weight: 500;
                                    font-size: 14px;
                                    transition: background 0.2s;
                                " onmouseover="this.style.background='rgba(255,255,255,0.15)'" 
                                   onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                                    Cancel
                                </button>
                                <button onclick="document.getElementById('file-upload-input').click();document.getElementById('fbx-version-modal').remove()" style="
                                    flex: 1;
                                    background: rgba(34,197,94,0.2);
                                    color: #22c55e;
                                    padding: 12px 20px;
                                    border-radius: 10px;
                                    border: 1px solid rgba(34,197,94,0.3);
                                    cursor: pointer;
                                    font-weight: 500;
                                    font-size: 14px;
                                    transition: background 0.2s;
                                " onmouseover="this.style.background='rgba(34,197,94,0.3)'" 
                                   onmouseout="this.style.background='rgba(34,197,94,0.2)'">
                                    Upload Different File
                                </button>
                            </div>
                        </div>
                        
                        <p style="
                            color: #606070;
                            font-size: 12px;
                            text-align: center;
                            margin: 20px 0 0;
                        ">
                            Supported formats: STL, OBJ, FBX 7.x, GLB, GLTF
                        </p>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Close on backdrop click
            modal.querySelector('div').addEventListener('click', (e) => {
                if (e.target === modal.querySelector('div')) {
                    modal.remove();
                }
            });
        }
        
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `<span>${type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'}</span> ${message}`;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
        
        // ═══════════════════════════════════════════════════════════════
        // SAVE PROJECT MODAL
        // ═══════════════════════════════════════════════════════════════
        
        const saveModal = document.getElementById('save-modal');
        const saveFilenameInput = document.getElementById('save-filename');
        const saveBtn = document.getElementById('save-btn');
        const saveModalClose = document.getElementById('save-modal-close');
        const saveModalCancel = document.getElementById('save-modal-cancel');
        const saveModalConfirm = document.getElementById('save-modal-confirm');
        
        // Open modal
        saveBtn.addEventListener('click', () => {
            if (!platformState.mesh) {
                showToast('No model to save', 'warning');
                return;
            }
            // Set default filename from original model name
            const originalName = platformState.geometryData?.name || 'project';
            const cleanName = originalName.replace(/\.[^/.]+$/, ''); // Remove extension
            saveFilenameInput.value = cleanName;
            saveFilenameInput.placeholder = cleanName;
            saveModal.classList.add('active');
            saveFilenameInput.focus();
            saveFilenameInput.select();
        });
        
        // Close modal functions
        function closeSaveModal() {
            saveModal.classList.remove('active');
        }
        
        saveModalClose.addEventListener('click', closeSaveModal);
        saveModalCancel.addEventListener('click', closeSaveModal);
        saveModal.addEventListener('click', (e) => {
            if (e.target === saveModal) closeSaveModal();
        });
        
        // Handle Enter key
        saveFilenameInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                saveModalConfirm.click();
            } else if (e.key === 'Escape') {
                closeSaveModal();
            }
        });
        
        // Save and download
        saveModalConfirm.addEventListener('click', async () => {
            const filename = saveFilenameInput.value.trim() || platformState.geometryData?.name || 'project';
            
            saveModalConfirm.disabled = true;
            saveModalConfirm.innerHTML = '<span>⏳</span> Generating...';
            
            try {
                // Get current state values
                const currentColor = platformState.mesh.material.color.getHexString();
                const currentMetalness = platformState.mesh.material.metalness;
                const currentRoughness = platformState.mesh.material.roughness;
                const bgColor = '#' + platformState.scene.background.getHexString();
                
                // Get transform values
                const position = {
                    x: platformState.mesh.position.x,
                    y: platformState.mesh.position.y,
                    z: platformState.mesh.position.z
                };
                const rotation = {
                    x: platformState.mesh.rotation.x,
                    y: platformState.mesh.rotation.y,
                    z: platformState.mesh.rotation.z
                };
                const scale = platformState.mesh.scale.x;
                
                console.log('💾 Save HTML - Rotation:', rotation);
                console.log('💾 Save HTML - Rotation degrees:', 
                    (rotation.x * 180/Math.PI).toFixed(1),
                    (rotation.y * 180/Math.PI).toFixed(1),
                    (rotation.z * 180/Math.PI).toFixed(1)
                );
                
                // Create geometry data WITH settings embedded (same as Export section)
                const geometryDataWithSettings = {
                    vertices: platformState.geometryData.vertices,
                    name: filename,
                    settings: {
                        color: '#' + currentColor,
                        metalness: currentMetalness,
                        roughness: currentRoughness,
                        position: position,
                        rotation: rotation,
                        scale: scale,
                        background: bgColor
                    }
                };
                
                // Generate HTML viewer with settings embedded
                const htmlContent = generateHTMLViewer(geometryDataWithSettings, filename, {
                    color: '#' + currentColor,
                    metalness: currentMetalness,
                    roughness: currentRoughness,
                    bgColor: bgColor
                });
                
                // Download
                const blob = new Blob([htmlContent], { type: 'text/html' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename + '.html';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                closeSaveModal();
                showToast(`Project saved: ${filename}.html`, 'success');
                
            } catch (error) {
                console.error('Save error:', error);
                showToast('Failed to save project: ' + error.message, 'error');
            }
            
            saveModalConfirm.disabled = false;
            saveModalConfirm.innerHTML = '<span>💾</span> Save & Download';
        });
        
        // ═══════════════════════════════════════════════════════════════
        // HTML VIEWER GENERATOR
        // ═══════════════════════════════════════════════════════════════
        
        function generateHTMLViewer(geometryData, title, options) {
            options = options || {};
            var color = options.color || '#6366f1';
            var metalness = options.metalness !== undefined ? options.metalness : 0.3;
            var roughness = options.roughness !== undefined ? options.roughness : 0.7;
            var bgColor = options.bgColor || '#1a1a2e';
            var geometryJson = JSON.stringify(geometryData);
            
            var html = '<!DOCTYPE html>\n';
            html += '<html lang="en">\n<head>\n';
            html += '<meta charset="UTF-8">\n';
            html += '<meta name="viewport" content="width=device-width, initial-scale=1.0">\n';
            html += '<title>' + title + ' - Fusion 3D Viewer</title>\n';
            html += '<style>\n';
            html += '* { margin: 0; padding: 0; box-sizing: border-box; }\n';
            html += 'body { background: ' + bgColor + '; overflow: hidden; }\n';
            html += '#viewer { width: 100vw; height: 100vh; }\n';
            html += '#loading { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #6366f1; font-family: system-ui, sans-serif; font-size: 18px; text-align: center; }\n';
            html += '#loading .spinner { width: 40px; height: 40px; border: 3px solid #2a2a4a; border-top-color: #6366f1; border-radius: 50%; margin: 0 auto 15px; animation: spin 1s linear infinite; }\n';
            html += '@keyframes spin { to { transform: rotate(360deg); } }\n';
            html += '#progress-bar { width: 200px; height: 4px; background: #2a2a4a; border-radius: 2px; margin-top: 10px; overflow: hidden; }\n';
            html += '#progress-fill { width: 0%; height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6); transition: width 0.3s ease; }\n';
            html += '#info { position: fixed; bottom: 15px; left: 15px; color: rgba(255,255,255,0.6); font-family: system-ui; font-size: 12px; }\n';
            html += '</style>\n</head>\n<body>\n';
            html += '<div id="viewer"></div>\n';
            html += '<div id="loading"><div class="spinner"></div><div id="status">Loading 3D Viewer...</div><div id="progress-bar"><div id="progress-fill"></div></div></div>\n';
            html += '<div id="info">Created with Fusion Platform</div>\n';
            html += '<script type="application/json" id="geometry-data">' + geometryJson + '<\/script>\n';
            html += '<script src="https://unpkg.com/three@0.160.0/build/three.min.js"><\/script>\n';
            html += '<script>\n';
            html += 'var statusEl=document.getElementById("status"),progressFill=document.getElementById("progress-fill");function updateStatus(e,t){statusEl.textContent=e,void 0!==t&&(progressFill.style.width=t+"%")}function defineOrbitControls(){if(!window.OrbitControls){class e extends THREE.EventDispatcher{constructor(e,t){super(),this.object=e,this.domElement=t,this.domElement.style.touchAction="none",this.enabled=!0,this.target=new THREE.Vector3,this.enableDamping=!0,this.dampingFactor=.05,this.enableZoom=!0,this.zoomSpeed=1,this.enableRotate=!0,this.rotateSpeed=1,this.enablePan=!0,this.panSpeed=1,this.minDistance=0,this.maxDistance=1/0,this.minPolarAngle=0,this.maxPolarAngle=Math.PI;var i=this,s={NONE:-1,ROTATE:0,DOLLY:1,PAN:2},o=s.NONE,a=new THREE.Spherical,n=new THREE.Spherical,r=1,l=new THREE.Vector3,h=new THREE.Vector2,c=new THREE.Vector2,d=new THREE.Vector2,p=new THREE.Vector2,u=new THREE.Vector2,m=new THREE.Vector2,f=new THREE.Vector2,g=new THREE.Vector3,v=(new THREE.Quaternion).setFromUnitVectors(e.up,new THREE.Vector3(0,1,0)),y=v.clone().invert();this.update=function(){var e=i.object.position;return g.copy(e).sub(i.target),g.applyQuaternion(v),a.setFromVector3(g),i.enableDamping?(a.theta+=n.theta*i.dampingFactor,a.phi+=n.phi*i.dampingFactor):(a.theta+=n.theta,a.phi+=n.phi),a.phi=Math.max(i.minPolarAngle,Math.min(i.maxPolarAngle,a.phi)),a.makeSafe(),a.radius*=r,a.radius=Math.max(i.minDistance,Math.min(i.maxDistance,a.radius)),i.enableDamping?i.target.addScaledVector(l,i.dampingFactor):i.target.add(l),g.setFromSpherical(a),g.applyQuaternion(y),e.copy(i.target).add(g),i.object.lookAt(i.target),i.enableDamping?(n.theta*=1-i.dampingFactor,n.phi*=1-i.dampingFactor,l.multiplyScalar(1-i.dampingFactor)):(n.set(0,0,0),l.set(0,0,0)),r=1,!1};var w=new THREE.Vector3;function b(e){n.theta-=e}function x(e){n.phi-=e}function E(e,t){w.setFromMatrixColumn(t,0),w.multiplyScalar(-e),l.add(w)}function T(e,t){w.setFromMatrixColumn(t,1),w.multiplyScalar(e),l.add(w)}function M(e,t){var s=i.domElement;g.copy(i.object.position).sub(i.target);var o=g.length()*Math.tan(i.object.fov/2*Math.PI/180);E(2*e*o/s.clientHeight,i.object.matrix),T(2*t*o/s.clientHeight,i.object.matrix)}function S(e){r/=e}function P(e){r*=e}function C(){return Math.pow(.95,i.zoomSpeed)}function L(e){i.enabled&&(0===e.button?(o=s.ROTATE,h.set(e.clientX,e.clientY)):1===e.button?(o=s.DOLLY,m.set(e.clientX,e.clientY)):2===e.button&&(o=s.PAN,p.set(e.clientX,e.clientY)),o!==s.NONE&&(document.addEventListener("pointermove",O),document.addEventListener("pointerup",R)))}function O(e){if(i.enabled)if(o===s.ROTATE)c.set(e.clientX,e.clientY),d.subVectors(c,h).multiplyScalar(i.rotateSpeed),b(2*Math.PI*d.x/i.domElement.clientHeight),x(2*Math.PI*d.y/i.domElement.clientHeight),h.copy(c);else if(o===s.DOLLY){var t=new THREE.Vector2(e.clientX,e.clientY),a=t.clone().sub(m);a.y>0?S(C()):a.y<0&&P(C()),m.copy(t)}else o===s.PAN&&(u.set(e.clientX,e.clientY),M((f=u.clone().sub(p).multiplyScalar(i.panSpeed)).x,f.y),p.copy(u));var f;i.update()}function R(){document.removeEventListener("pointermove",O),document.removeEventListener("pointerup",R),o=s.NONE}function D(e){i.enabled&&i.enableZoom&&(e.preventDefault(),e.deltaY<0?P(C()):e.deltaY>0&&S(C()),i.update())}i.domElement.addEventListener("contextmenu",function(e){return e.preventDefault()}),i.domElement.addEventListener("pointerdown",L),i.domElement.addEventListener("wheel",D,{passive:!1}),this.update()}}window.OrbitControls=e}}var loadAttempts=0;!function e(){loadAttempts++,"undefined"==typeof THREE?loadAttempts>200?(updateStatus("Failed to load 3D library",0),statusEl.style.color="#ef4444"):setTimeout(e,50):(defineOrbitControls(),updateStatus("Initializing...",20),setTimeout(initViewer,10))}();function initViewer(){try{var e=document.getElementById("viewer"),t=new THREE.Scene;t.background=new THREE.Color("' + bgColor + '");var i=new THREE.PerspectiveCamera(45,window.innerWidth/window.innerHeight,.01,1e3);i.position.set(5,5,5);var s=new THREE.WebGLRenderer({antialias:!0});s.setSize(window.innerWidth,window.innerHeight),e.appendChild(s.domElement);var o=new OrbitControls(i,s.domElement);o.target.set(0,0,0),t.add(new THREE.AmbientLight(16777215,.6));var a=new THREE.DirectionalLight(16777215,1);a.position.set(5,10,7),t.add(a),updateStatus("Loading geometry...",40),setTimeout(function(){var e=JSON.parse(document.getElementById("geometry-data").textContent);updateStatus("Building model...",60),setTimeout(function(){var a=new Float32Array(e.vertices),n=new THREE.BufferGeometry;n.setAttribute("position",new THREE.BufferAttribute(a,3)),n.computeVertexNormals();var r=new THREE.MeshStandardMaterial({color:"' + color + '",metalness:' + metalness + ',roughness:' + roughness + ',side:THREE.DoubleSide}),l=new THREE.Mesh(n,r);n.computeBoundingBox();var h=n.boundingBox.getCenter(new THREE.Vector3),c=n.boundingBox.getSize(new THREE.Vector3),d=3/Math.max(c.x,c.y,c.z);n.translate(-h.x,-h.y,-h.z),l.scale.setScalar(d),t.add(l),t.add(new THREE.GridHelper(20,40,6513393,2763722)),updateStatus("Complete!",100),setTimeout(function(){document.getElementById("loading").style.display="none"},500),function e(){requestAnimationFrame(e),o.update(),s.render(t,i)}(),window.addEventListener("resize",function(){i.aspect=window.innerWidth/window.innerHeight,i.updateProjectionMatrix(),s.setSize(window.innerWidth,window.innerHeight)})},10)},10)}catch(e){console.error(e),updateStatus("Error: "+e.message,0),statusEl.style.color="#ef4444"}}\n';
            html += '<\/script>\n</body>\n</html>';
            
            return html;
        }
    </script>

<!-- Back to Catalog Button -->
<a href="index.php" id="backToCatalogBtn" class="catalog-back-btn" style="position: fixed; bottom: 30px; left: 30px; width: 70px; height: 70px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 25px rgba(240, 147, 251, 0.5); z-index: 9999; text-decoration: none; transition: all 0.3s ease; border: 3px solid rgba(255, 255, 255, 0.3); animation: catalog-pulse 2s infinite;" title="Back to Catalog" onmouseover="this.style.transform='scale(1.15) rotate(-10deg)'; this.style.boxShadow='0 10px 35px rgba(240, 147, 251, 0.7)';" onmouseout="this.style.transform='scale(1) rotate(0deg)'; this.style.boxShadow='0 8px 25px rgba(240, 147, 251, 0.5)';">
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
        <polyline points="9 22 9 12 15 12 15 22"></polyline>
    </svg>
</a>
<style>
@keyframes catalog-pulse {
    0%, 100% { box-shadow: 0 8px 25px rgba(240, 147, 251, 0.5), 0 0 0 0 rgba(240, 147, 251, 0.4); }
    50% { box-shadow: 0 8px 25px rgba(240, 147, 251, 0.5), 0 0 0 10px rgba(240, 147, 251, 0); }
}

@keyframes logoFloat {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    25% { transform: translateY(-8px) rotate(-2deg); }
    50% { transform: translateY(-12px) rotate(0deg); }
    75% { transform: translateY(-8px) rotate(2deg); }
}
.catalog-back-btn::after {
    content: 'Catalog';
    position: absolute;
    left: 85px;
    background: rgba(0, 0, 0, 0.85);
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.catalog-back-btn:hover::after {
    opacity: 1;
}
</style>
<!-- End Back to Catalog Button -->
</body>
</html>
