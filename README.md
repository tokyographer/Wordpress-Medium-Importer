# WP Medium Importer

### Description
WP Medium Importer is a WordPress plugin that allows users to import their Medium posts into WordPress. The plugin supports importing Medium posts from a zip archive, automatically handling images and post metadata such as titles and dates. This plugin is useful for bloggers and writers looking to migrate their Medium content into a self-hosted WordPress website.

### Features
- Import Medium posts from a zip archive.
- Automatically handle images, uploading them to WordPress media library.
- Automatically set post title, content, and post date based on the Medium export file format.
- Simple admin interface to upload and import posts with progress feedback.

### Installation

1. Download the plugin as a zip file or clone the repository into your WordPress `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the 'Medium Importer' page in the WordPress admin dashboard.
4. Upload your Medium export zip file and start the import.

### Usage

1. Export your Medium posts by following [Medium's export guide](https://help.medium.com/hc/en-us/articles/214874118-Export-your-posts).
2. Upload the zip file through the 'Medium Importer' page in the WordPress dashboard.
3. Monitor the import process in real-time, with progress displayed during post import.
4. After import, your posts will be available in the WordPress post editor for review.

### Requirements
- WordPress 4.0 or later
- PHP 5.6 or later
- The zip extension for PHP must be enabled on your server

### Frequently Asked Questions

#### Q: What file format does the plugin accept?
A: The plugin accepts a zip file that contains exported Medium posts. The posts should be in `.html` format inside the zip archive.

#### Q: How does the plugin handle images?
A: The plugin uploads images from your Medium posts to the WordPress media library and updates the image URLs in the post content accordingly.

#### Q: Can I use this plugin with draft Medium posts?
A: No, the plugin currently skips Medium drafts. Only published posts are imported.

### Contributing
We welcome contributions to improve this plugin. You can contribute by:
- Reporting issues and bugs.
- Submitting pull requests to fix bugs or add features.
- Suggesting improvements or new features.

### Changelog

#### Version 1.8
- Initial release.

### License
This plugin is licensed under the GPL-2.0 License. For more information, visit [GPL License](https://www.gnu.org/licenses/gpl-2.0.html).

---

### Author
Anthony Tatekawa
