# Magento 2 Log Viewer

Mageprince Log Viewer is a powerful admin utility that allows you to manage, monitor, and debug log files directly from the Magento Admin Panel — without needing to access the server or filesystem.

# ✅ Compatibility

<b>Magento Open Source:</b> 2.3.x - 2.4.x </br>

# ✨ Key Features

- View Magento log files (var/log/) directly in the admin panel
- Display latest log lines with “Load Previous” functionality
- Search log files by filename
- Sort logs by filename, or last updated time
- Download or delete log files from admin
- Pagination support for large log directories
- Admin configuration for:
  - Enable/disable the extension
  - Set number of log lines to show
  - Set how many log files to list per page
  - Define default sort column and direction
  - Restrict allowed file types
  - Allow or restrict file deletion
  - Allow or restrict file download

# 🚀 Installation Instructions

### 1. Install via composer (Recommended)

Run the following Magento CLI commands:

```
composer require mageprince/module-log-viewer
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

### 2. Manual Installation

Copy the content of the repo to the Magento 2 `app/code/Mageprince/LogViewer`

Run the following Magento CLI commands:
```
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




