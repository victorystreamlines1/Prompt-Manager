# Prompt Manager Platform

A comprehensive PHP-based platform featuring:
- 🎨 **3D Model Editor** - Edit and export 3D models (STL, OBJ, FBX, GLB)
- 📝 **Prompt Manager** - Manage AI prompts and templates
- 📊 **PHP Dashboard** - MySQL database management
- 📁 **Catalog** - Browse and manage all applications

## 🚀 Quick Start

### Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10+
- Web server (Apache/Nginx) or PHP built-in server

### Installation

1. **Clone/Download** the project to your web root:
   ```bash
   cd /path/to/laragon/www  # or xampp/htdocs
   git clone <repo-url> Prompt-Manager
   ```

2. **Configure environment** (optional):
   ```bash
   cp env.example.txt .env
   # Edit .env with your settings
   ```

3. **Run the application**:
   ```bash
   # Using PHP built-in server
   php -S localhost:8000
   
   # Or access via Laragon/XAMPP
   # http://localhost/Prompt-Manager
   ```

4. **Login** with default password: `GL_Admin`

## 📁 Project Structure

```
Prompt-Manager/
├── config/                 # Configuration files
│   ├── app.php            # Application settings
│   ├── auth.php           # Authentication config
│   └── database.php       # Database connections
│
├── src/                   # Source code
│   ├── Core/              # Core services
│   │   ├── Auth/          # Authentication
│   │   ├── Database/      # Database connection
│   │   ├── Http/          # Request/Response handling
│   │   └── Utils/         # Utilities
│   │
│   ├── Modules/           # Application modules
│   │   ├── Catalog/
│   │   ├── PromptManager/
│   │   ├── Dashboard/
│   │   └── Platform3D/
│   │
│   └── bootstrap.php      # Application bootstrap
│
├── public/                # Public assets
│   ├── assets/
│   │   ├── css/          # Stylesheets
│   │   └── js/           # JavaScript
│   └── uploads/          # Uploaded files
│
├── templates/             # View templates
│   ├── layouts/          # Page layouts
│   └── partials/         # Reusable components
│
├── storage/              # Application storage
│   ├── logs/            # Log files
│   └── cache/           # Cache files
│
├── uploads/              # Legacy uploads folder
│
└── *.php                 # Entry point files (legacy)
```

## 🔧 Configuration

### Environment Variables

Copy `env.example.txt` to `.env` and configure:

```env
# Application
APP_NAME="Prompt Manager"
APP_ENV=local
APP_DEBUG=true

# Authentication
AUTH_PASSWORD=your_secure_password
AUTH_SALT=random_string_here

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_database
DB_USERNAME=root
DB_PASSWORD=
```

### Database Setup

The application supports:
- **Local MySQL** (Laragon/XAMPP)
- **Remote MySQL** (Hostinger, etc.)

Tables are created automatically on first use.

## 📖 Modules

### 3D Platform (`3d-platform.php`)
Advanced 3D model editor with:
- Support for STL, OBJ, FBX, GLB, GLTF
- Transform tools (move, rotate, scale)
- Material editing (color, metalness, roughness)
- Export to multiple formats

### Prompt Manager (`Prompt-Manager.php`)
Manage AI prompts with:
- Create and edit prompts
- Template management
- File uploads
- Database storage

### PHP Dashboard (`PHP-Dashboard.php`)
Database administration:
- Browse databases and tables
- Create/edit tables
- Execute queries
- Import/export data

### Catalog (`index.php`)
Main entry point:
- Browse all PHP files
- Admin authentication
- Quick access to all modules

## 🔐 Security

- Password-protected admin access
- Session-based authentication
- Remember-me functionality (30 days)
- SQL injection prevention via PDO
- XSS protection via output escaping

## 🛠 Development

### Adding New Modules

1. Create module folder in `src/Modules/YourModule/`
2. Add controller and service classes
3. Create views in `templates/`
4. Add CSS/JS in `public/assets/`

### Code Standards

- PSR-4 autoloading for `App\` namespace
- PSR-12 coding style
- Type hints where possible
- Documented functions with PHPDoc

## 📝 API Conventions

All modules use a consistent API pattern:

```php
// Request
POST /module.php
action=action_name
param1=value1

// Response (JSON)
{
    "success": true,
    "message": "Operation completed",
    "data": { ... }
}
```

## 🐛 Troubleshooting

### Database Connection Issues
- Ensure MySQL is running
- Try `127.0.0.1` instead of `localhost`
- Check username/password (Laragon default: root with empty password)

### 3D Files Not Loading
- Check file format is supported
- Ensure file is not corrupted
- Check browser console for errors

### Authentication Issues
- Clear browser cookies
- Check session configuration
- Verify password in config

## 📄 License

MIT License - feel free to use and modify.

## 🤝 Contributing

1. Fork the repository
2. Create feature branch
3. Make changes
4. Submit pull request

---

Built with ❤️ using PHP, Three.js, and MySQL

