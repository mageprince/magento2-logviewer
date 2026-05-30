# Magento 2 Log Viewer

Mageprince Log Viewer is a powerful admin utility that allows you to manage, monitor, and debug log files directly from the Magento Admin Panel — without needing to access the server or filesystem.

# ✅ Compatibility

<b>Magento Open Source:</b> 2.3.x - 2.4.x </br>

# ✨ Key Features

### Log File List
- Browse all log files in `var/log/` directly from the admin panel
- Search log files by filename
- Sort by filename, file size, or last modified time
- Pagination support for large log directories

### Log Viewer
- View the latest log lines with configurable line count
- **Load Previous Logs** — paginate backwards through the file without loading it all at once
- **Live Log** — auto-refreshes every 3 seconds to tail new entries in real time
- **Wrap Lines** toggle for better readability of long lines
- **Clear Log** — clears the current view without affecting the file on disk

### Full-File Search
- Search across the **entire log file**, not just the visible lines — works on files of any size (including multi-GB logs)
- Returns the last 20 matching lines; click **Load Previous Logs** to page through earlier results

### File Operations
- **Download** log files directly from the admin
- **Truncate** (clear file content) with confirmation prompt
- Role-based access control — download and delete can be independently restricted per admin role

### ⚙️ Admin Configuration (`Stores → Configuration → MagePrince → Log Viewer`)
| Setting | Description |
|---|---|
| Enable | Toggle the entire module on or off |
| Lines to Show | Number of lines tailed per view (default 500) |
| Items Per Page | Log file list page size (default 10) |
| Default Sort Column | `name`, `size`, or `mod_time` |
| Default Sort Direction | `asc` or `desc` |
| Allow Delete | Show or hide the Truncate button |
| Allow Download | Enable or disable file download |

### 🔒 Security
- Path traversal prevention — all file access is validated against `var/log/` using real path resolution
- Search query sanitisation — null bytes and control characters stripped before regex matching
- Role-based ACL with four granular resources: View, Download, Delete, Settings

# 🚀 Installation Instructions

### 1. Install via Composer (Recommended)

```bash
composer require mageprince/module-log-viewer
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

### 2. Manual Installation

Copy the contents of this repository to `app/code/Mageprince/LogViewer`, then run:

```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

# 🤝 Contribution

Want to contribute to this extension? The quickest way is to <a href="https://help.github.com/articles/about-pull-requests/">open a pull request</a> on GitHub.

# 🛠 Support

If you encounter any problems or bugs, please <a href="https://github.com/mageprince/magento2-logviewer/issues">open an issue</a> on GitHub.

# 📸 Screenshots

<img width="3402" height="2158" alt="image" src="https://github.com/user-attachments/assets/eb0cef0d-3ee5-4346-8607-551fca6938e6" />
<img width="3400" height="2154" alt="1-log-list" src="https://github.com/user-attachments/assets/aa975dda-902e-4f24-b421-095e73ee88da" />
<img width="3398" height="2154" alt="3-admin-config" src="https://github.com/user-attachments/assets/1711da37-4128-4f7f-a44f-3422d6519988" />
