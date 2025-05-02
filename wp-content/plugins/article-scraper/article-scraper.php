<?php
/*
Plugin Name: Article Scraper
Description: Scrapes articles from a URL and saves them as drafts in WordPress.
Version: 1.0
Author: fk
*/

require_once plugin_dir_path(__FILE__) . 'scraper-functions.php'; // Include the functions file

// Register admin menu
add_action('admin_menu', function () {
    add_menu_page(
        'Article Scraper',      // Page title
        'Article Scraper',      // Menu title
        'manage_options',       // Capability
        'article-scraper',      // Menu slug (URL)
        'aas_article_scraper_page',  // Callback function
        'dashicons-admin-site-alt3', // Icon
        6                       // Menu position
    );
});

// Admin page callback function
function aas_article_scraper_page()
{
?>
    <div class="wrap">
        <h1>Article Scraper</h1>

        <?php
        if (isset($_POST['aas_scrape_url']) && !empty($_POST['aas_scrape_url'])) {
            $url = esc_url_raw($_POST['aas_scrape_url']);

            // Call the scraper function
            $post_id = aas_scrape_single_article($url);

            if ($post_id) {
                echo '<div class="notice notice-success is-dismissible"><p>Article successfully created as draft! <a href="' . get_edit_post_link($post_id) . '">Edit Post</a></p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Failed to scrape the article. Please check the URL.</p></div>';
            }
        }
        ?>

        <form method="post">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Article URL</th>
                    <td><input type="url" name="aas_scrape_url" style="width: 400px;" required /></td>
                </tr>
            </table>

            <?php submit_button('Fetch Article'); ?>
        </form>
    </div>
<?php
}
