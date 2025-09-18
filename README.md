# 🗄️ PHPLiteAdmin v5.0 - Modern UI Edition

A modern, responsive web interface for managing SQLite databases using PHP. This project is a complete redesign of the classic PHPLiteAdmin, rebuilt with a sleek modern design and advanced features.

![PHPLiteAdmin v5.0](https://img.shields.io/badge/Version-5.0-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-brightgreen.svg)
![SQLite](https://img.shields.io/badge/SQLite-3.x-lightblue.svg)
![License](https://img.shields.io/badge/License-MIT-green.svg)

## ✨ Key Features

### 🎨 Modern User Interface
- ✅ Fully responsive, mobile-friendly design
- ✅ Dark theme with customizable colors
- ✅ Collapsible sidebar for easy navigation
- ✅ Font Awesome icons for enhanced visuals
- ✅ Smooth animations and CSS transitions

### 🛠️ Complete Database Management
- ✅ Multiple SQLite database support
- ✅ Create, rename, and delete databases
- ✅ VACUUM operations for optimization
- ✅ Database details and statistics view

### 📊 Advanced Table Management
- ✅ Create tables with detailed column configuration
- ✅ Modify existing table structure
- ✅ Drop and truncate tables
- ✅ View metadata and technical info

### 💾 Import/Export Data
- ✅ Export full schema and data in SQL format
- ✅ Export individual tables as CSV
- ✅ Advanced SQL import with error handling
- ✅ CSV import with customizable options:
  - Custom delimiter
  - Configurable escape character
  - Header row handling
  - NULL value replacement

### 🔍 Query Builder & SQL Editor
- ✅ Manual SQL editor with syntax highlighting
- ✅ Visual query builder for SELECT, INSERT, UPDATE, DELETE
- ✅ Dynamic WHERE clause construction
- ✅ GROUP BY and ORDER BY support
- ✅ Query preview before execution

### 📋 Data Browsing & Editing
- ✅ Paginated data view
- ✅ New record insertion with type validation
- ✅ Inline data editing
- ✅ Record deletion with confirmation
- ✅ Full BLOB support:
  - File uploads
  - Image previews
  - File downloads

### 🔐 Security & Authentication
- ✅ Administrator password authentication
- ✅ SQL injection protection
- ✅ Strict input validation
- ✅ Secure session management

## 🚀 Quick Installation

### System Requirements
- **PHP** ≥ 7.4 with extensions:
  - PDO
  - SQLite3 
  - GD (optional, for image preview)
- **Web Server** (Apache, Nginx, or PHP built-in server)

### Setup Instructions
1. Download `phpliteadmin_v5.0_modern_ui_edition.php`
2. Upload the file to your web server
3. Edit initial settings in the file:

```php
// CUSTOM CONFIGURATION
define('APPNAME', 'PHPLiteAdmin v5.0 - Modern UI Edition');
define('ADMINPASS', 'admin123'); // ⚠️ Change this password!

// Theme colors (hex without #)
define('PRIMARYCOLOR', '2563eb');    // Primary blue
define('PRIMARYDARK', '1d4ed8');     // Dark blue
define('SUCCESSCOLOR', '059669');    // Success green
define('DANGERCOLOR', 'dc2626');     // Danger red
// ... other customizable colors
```

4. Navigate to the file URL in your browser
5. Enter the configured admin password

## 💡 Usage Guide

### First-Time Access
1. **Login**: Enter the admin password
2. **Create Database**: Use the sidebar module to create your first database
3. **Navigate**: Select a database from the list to start working

### Primary Operations

#### Database Management
- **New Database**: Enter a name in the sidebar and click "Create Database"
- **Select**: Click a database name to view details
- **Rename**: Use the "Rename Database" form in the sidebar
- **Delete**: Click the trash icon next to a database

#### Table Management
- **New Table**: Go to "New Table" and configure columns, types, and constraints
- **Browse Data**: Click "Browse" to view and edit records
- **Alter Structure**: Use "Structure" to modify columns
- **Truncate/Drop**: Available from the tables list

#### SQL Queries
- **Manual Editor**: Write custom SQL in the editor
- **Query Builder**: Visually construct complex queries
- **Export**: Save results or schema as SQL/CSV

## 🎨 Customization

### Theme & Colors
Modify CSS variables by editing PHP constants:

```php
define('BACKGROUNDCOLOR', 'f8fafc'); // Main background
define('SIDEBARBG', '1e293b');       // Sidebar background
define('CARDBG', 'ffffff');        // Card background
```

### Database Directory
Default path is `./databases/`. Change with:

```php
define('DATABASESPATH', './my-databases/');
```

## 🔧 Advanced Features

### Configurable CSV Import
- Custom delimiter (comma, semicolon, tab)
- Custom quote character
- Custom escape character
- NULL value handling
- Automatic header detection

### BLOB Management
- Upload any file type
- Automatic image previews
- Direct binary download
- Optimized memory handling for large files

### Visual Query Builder
- Drag-and-drop interface
- Advanced comparison operators
- Planned JOIN support in future releases
- Export built queries

## 🐛 Troubleshooting

### Common Errors

**❌ "Unable to connect to database"**
- Check read/write permissions on your database directory
- Ensure PDO_SQLITE extension is enabled

**❌ "Incorrect password"**
- Update `ADMINPASS` constant
- Clear browser cache if issue persists

**❌ "CSV import error"**
- Verify CSV format
- Check delimiter settings
- Ensure target table exists

### Error Logging
Errors are caught and displayed in the UI. Enable PHP error logging for advanced debugging.

## 🤝 Contributing

This project is open source! Contributions are welcome:

1. Fork the repository
2. Create a branch (`git checkout -b feature/new-feature`)
3. Commit changes (`git commit -am 'Add new feature'`)
4. Push to branch (`git push origin feature/new-feature`)
5. Open a Pull Request

### Future Roadmap
- [ ] Advanced JOIN support in Query Builder
- [ ] Plugin architecture
- [ ] REST API for external integration
- [ ] Scheduled automatic backups
- [ ] Simultaneous multi-database support
- [ ] Visual relationship editor

## 📄 License

Distributed under the MIT License. See `LICENSE` for details.

## 🏆 Credits

**Developer**: Daniele Deplano  
**Developer**: Roberto Viola
**Inspired by**: Original PHPLiteAdmin project  
**Tech Stack**: PHP, SQLite, HTML5, CSS3, JavaScript  
**AI Assistant**: Assisted by Perplexity AI and DeepSeek

## 📞 Support

For support, bug reports, or feature requests:
- Open an Issue on GitHub
- Contact the developer via email
- Join community discussions

---

⭐ **If you find this project useful, give it a star on GitHub!** ⭐

*Last updated: September 2025*

<img width="1920" height="1080" alt="Schermata a 2025-09-18 13-22-38" src="https://github.com/user-attachments/assets/d5373958-84f9-44d1-9983-e6f6bf363a1b" />
<img width="1920" height="1080" alt="Schermata a 2025-09-18 13-23-15" src="https://github.com/user-attachments/assets/76ce6ec9-431a-4a2a-9ba1-a6799ad850ef" />
<img width="1920" height="1080" alt="Schermata a 2025-09-18 13-23-50" src="https://github.com/user-attachments/assets/0625fe09-33d8-4cb9-812c-a0e1a92eed53" />
<img width="1920" height="1080" alt="Schermata a 2025-09-18 13-24-40" src="https://github.com/user-attachments/assets/a4bd205a-34ba-4b77-bf21-580f11413e17" />
<img width="1920" height="1080" alt="Schermata a 2025-09-18 13-28-30" src="https://github.com/user-attachments/assets/67ed7114-0405-4026-8cf8-a7bc27c81321" />






