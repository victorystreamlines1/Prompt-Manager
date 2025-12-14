# Report Prompter

A PHP-based AI Prompt Generator with admin authentication.

## Overview

This is a single-page PHP application that provides:
- Admin login with password protection (password: GL_Admin)
- Prompt template management
- File upload functionality
- Database-backed storage for prompts and templates

## Tech Stack

- **Language**: PHP 8.4
- **Database**: External MySQL database (hosted on Hostinger)
- **Frontend**: HTML/CSS/JavaScript (embedded in PHP)

## Project Structure

```
/
├── index.php              # Main application file (contains all logic and UI)
├── prompt.txt             # Original prompt file
├── prompt-manager-report.html  # Static report file
└── uploads/               # Directory for uploaded files (created at runtime)
```

## Running the Application

The PHP built-in server runs on port 5000:
```bash
php -S 0.0.0.0:5000
```

## Database Configuration

The application connects to an external MySQL database. The connection details are currently hardcoded in `index.php`. For production use, consider moving these to environment variables.

## Authentication

- Single admin password: `GL_Admin`
- Features "Remember me" functionality (30 days)
- Password visibility toggle
