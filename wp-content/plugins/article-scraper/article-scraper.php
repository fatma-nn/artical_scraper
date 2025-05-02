<?php
/*
Plugin Name: Article Scraper
Description: Scrapes articles from a URL and saves them as drafts in WordPress.
Version: 1.0
Author: fk
*/

// Register admin menu
add_action('admin_menu', function () {
    add_menu_page(
        'Article Scraper',
        'Article Scraper',
        'manage_options',
        'article-scraper',
        'aas_article_scraper_page',
        'dashicons-admin-site-alt3',
        6
    );
});

// Admin page callback
function aas_article_scraper_page()
{
    ?>
    <div class="wrap">
        <h1>Article Scraper</h1>

        <?php
        if (isset($_POST['aas_scrape_url']) && !empty($_POST['aas_scrape_url'])) {
            $url = esc_url_raw($_POST['aas_scrape_url']);
            $selector = !empty($_POST['aas_selector']) ? sanitize_text_field($_POST['aas_selector']) : null;

            $post_id = aas_scrape_single_article($url, $selector);

            if ($post_id) {
                echo '<div class="notice notice-success is-dismissible"><p>Article successfully created as draft! <a href="' . get_edit_post_link($post_id) . '">Edit Post</a></p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Failed to scrape the article. Please check the URL and selector.</p></div>';
            }
        }
        ?>

        <form method="post">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Article URL</th>
                    <td><input type="url" name="aas_scrape_url" style="width: 400px;" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Content Selector (optional)</th>
                    <td><input type="text" name="aas_selector" placeholder=".post-content or #main" style="width: 400px;" /></td>
                </tr>
            </table>

            <?php submit_button('Fetch Article'); ?>
        </form>
    </div>
    <?php
}

// Scraper logic
function aas_scrape_single_article($url, $selector = null)
{
    $response = wp_remote_get($url);
    if (is_wp_error($response)) return false;

    $html = wp_remote_retrieve_body($response);
    if (empty($html)) return false;

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    // Remove unwanted tags
    foreach (['header', 'footer', 'nav', 'aside'] as $tag) {
        $elements = $dom->getElementsByTagName($tag);
        while ($elements->length > 0) {
            $element = $elements->item(0);
            $element->parentNode->removeChild($element);
        }
    }

    // Get page title
    $title = 'No Title Found';
    $titleNodes = $dom->getElementsByTagName('title');
    if ($titleNodes->length > 0) {
        $title = $titleNodes->item(0)->nodeValue;
    }

    $xpath = new DOMXPath($dom);
    $contentNode = null;

    // Use custom selector if provided
    if ($selector) {
        $selector = trim($selector);
        if (strpos($selector, '#') === 0) {
            $id = substr($selector, 1);
            $nodes = $xpath->query("//*[@id='$id']");
        } elseif (strpos($selector, '.') === 0) {
            $class = substr($selector, 1);
            $nodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $class ')]");
        } else {
            $nodes = $xpath->query("//" . $selector);
        }

        if ($nodes->length > 0) {
            $contentNode = $nodes->item(0);
        }
    }

    // Fallback: article > main > largest div
    if (!$contentNode) {
        foreach (['article', 'main'] as $tag) {
            $nodes = $xpath->query("//{$tag}");
            if ($nodes->length > 0) {
                $contentNode = $nodes->item(0);
                break;
            }
        }
    }

    if (!$contentNode) {
        $divs = $xpath->query('//div');
        $maxText = 0;
        foreach ($divs as $div) {
            $text = trim($div->textContent);
            if (strlen($text) > $maxText) {
                $maxText = strlen($text);
                $contentNode = $div;
            }
        }
    }

    if (!$contentNode) return false;

    $content = $dom->saveHTML($contentNode);

    // Save post
    $post_data = [
        'post_title'   => wp_strip_all_tags($title),
        'post_content' => $content,
        'post_status'  => 'draft',
        'post_author'  => get_current_user_id(),
    ];

    $post_id = wp_insert_post($post_data);
    return (!is_wp_error($post_id)) ? $post_id : false;
}
?>
