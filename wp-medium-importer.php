<?php
/*
Plugin Name: WP Medium Importer
Description: Import Medium posts from a zip archive into WordPress.
Version: 1.7
Author: Your Name
*/

// Hook to add admin menu item
add_action('admin_menu', 'wpmi_add_admin_menu');
add_action('wp_ajax_wpmi_import_posts', 'wpmi_import_posts');
add_action('wp_ajax_wpmi_upload_zip', 'wpmi_handle_upload');

// Function to add admin menu item
function wpmi_add_admin_menu() {
    add_menu_page('Medium Importer', 'Medium Importer', 'manage_options', 'wp-medium-importer', 'wpmi_admin_page');
}

// Admin page content
function wpmi_admin_page() {
    ?>
    <div class="wrap">
        <h1>Medium Importer</h1>
        <form id="wpmi-upload-form" method="post" enctype="multipart/form-data">
            <input type="file" name="medium_zip" id="medium_zip" required>
            <input type="submit" name="upload_medium" value="Upload Medium Posts" class="button button-primary">
        </form>
        <div id="wpmi-progress" style="display:none;">
            <p>Importing posts...</p>
            <progress id="wpmi-progress-bar" value="0" max="100"></progress>
        </div>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#wpmi-upload-form').submit(function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                $('#wpmi-progress').show();
                $.ajax({
                    url: ajaxurl + '?action=wpmi_upload_zip',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            wpmi_import_posts(response.data.files);
                        } else {
                            alert('Failed to upload zip file: ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred while uploading the zip file: ' + error);
                    }
                });
            });

            function wpmi_import_posts(files) {
                var totalFiles = files.length;
                var processedFiles = 0;

                function importNext() {
                    if (processedFiles < totalFiles) {
                        $.ajax({
                            url: ajaxurl + '?action=wpmi_import_posts',
                            type: 'POST',
                            data: {
                                file: files[processedFiles]
                            },
                            success: function(response) {
                                if (response.success) {
                                    processedFiles++;
                                    var progress = Math.round((processedFiles / totalFiles) * 100);
                                    $('#wpmi-progress-bar').val(progress);
                                    importNext();
                                } else {
                                    alert('Failed to import post: ' + response.data);
                                    $('#wpmi-progress').hide();
                                }
                            },
                            error: function(xhr, status, error) {
                                alert('An error occurred while importing posts: ' + error);
                                $('#wpmi-progress').hide();
                            }
                        });
                    } else {
                        $('#wpmi-progress').hide();
                        alert('Import completed.');
                    }
                }

                importNext();
            }
        });
    </script>
    <?php
}

// Handle file upload
function wpmi_handle_upload() {
    if (!empty($_FILES['medium_zip']['tmp_name'])) {
        $file = $_FILES['medium_zip']['tmp_name'];
        $zip = new ZipArchive;

        if ($zip->open($file) === TRUE) {
            $extractPath = wp_upload_dir()['path'] . '/medium_import';
            $zip->extractTo($extractPath);
            $zip->close();
            $files = glob($extractPath . '/posts/*.html');
            if ($files === false) {
                error_log("wpmi_handle_upload: No files found in the extracted zip.");
                wp_send_json_error('No files found in the extracted zip.');
            } else {
                wp_send_json_success(array('files' => $files));
            }
        } else {
            error_log("wpmi_handle_upload: Failed to open the zip file.");
            wp_send_json_error('Failed to open the zip file.');
        }
    } else {
        error_log("wpmi_handle_upload: No file uploaded.");
        wp_send_json_error('No file uploaded.');
    }
}

// Import posts from extracted files
function wpmi_import_posts() {
    if (isset($_POST['file'])) {
        $file = sanitize_text_field($_POST['file']);
        if (file_exists($file) && strpos(basename($file), 'draft') === false) {
            error_log("wpmi_import_posts: Importing file $file");
            wpmi_import_post($file);
            wp_send_json_success();
        } else {
            error_log("wpmi_import_posts: Invalid file or draft detected - $file");
            wp_send_json_error('Invalid file or draft detected.');
        }
    } else {
        error_log("wpmi_import_posts: No file specified.");
        wp_send_json_error('No file specified.');
    }
}

// Import a single post
function wpmi_import_post($file) {
    $content = file_get_contents($file);
    if ($content === false) {
        error_log("wpmi_import_post: Failed to read file content - $file");
        return;
    }

    $doc = new DOMDocument();
    @$doc->loadHTML($content);

    // Extract post date, title, and content from file name and HTML content
    $filename = basename($file, ".html");
    $parts = explode('_', $filename, 3);
    if (count($parts) < 3) {
        error_log("wpmi_import_post: Invalid file name format - $filename");
        return;
    }
    $date = $parts[0];
    $title = preg_replace('/-[0-9a-f]{12}$/', '', str_replace('-', ' ', $parts[1]));
    $post_date = date('Y-m-d H:i:s', strtotime($date));

    $body = $doc->getElementsByTagName('body')->item(0);
    if (!$body) {
        error_log("wpmi_import_post: No body tag found in file - $file");
        return;
    }

    $innerHTML = '';
    foreach ($body->childNodes as $child) {
        $innerHTML .= $doc->saveHTML($child);
    }

    // Remove title from post body
    $innerHTML = preg_replace('/<h1[^>]*>.*?<\/h1>/', '', $innerHTML);

    // Handle images
    $images = $doc->getElementsByTagName('img');
    foreach ($images as $img) {
        $src = $img->getAttribute('src');
        if (filter_var($src, FILTER_VALIDATE_URL)) {
            $image_data = @file_get_contents($src);
            if ($image_data !== false) {
                $filename = basename($src);
                $upload_file = wp_upload_bits($filename, null, $image_data);

                if (!$upload_file['error']) {
                    $wp_filetype = wp_check_filetype($filename, null);
                    $attachment = array(
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title' => sanitize_file_name($filename),
                        'post_content' => '',
                        'post_status' => 'inherit',
                    );
                    $attachment_id = wp_insert_attachment($attachment, $upload_file['file']);
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attach_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
                    wp_update_attachment_metadata($attachment_id, $attach_data);
                    $new_src = wp_get_attachment_url($attachment_id);
                    $img->setAttribute('src', $new_src);
                } else {
                    error_log("wpmi_import_post: Failed to upload image - $filename");
                }
            } else {
                error_log("wpmi_import_post: Failed to read image data - $src");
            }
        } else {
            error_log("wpmi_import_post: Invalid image URL - $src");
        }
    }

    // Create new post with updated content
    $updatedContent = '';
    foreach ($body->childNodes as $child) {
        $updatedContent .= $doc->saveHTML($child);
    }

    $post_data = array(
        'post_title'    => $title,
        'post_content'  => $updatedContent,
        'post_status'   => 'publish',
        'post_date'     => $post_date,
    );

    $post_id = wp_insert_post($post_data);
    if (is_wp_error($post_id)) {
        error_log("wpmi_import_post: Failed to insert post - " . $post_id->get_error_message());
    } else {
        error_log("wpmi_import_post: Successfully inserted post - $post_id");
    }
}
?>
