<?php
/*
Plugin Name: Article Scraper
Description: Scrapes articles from a URL and saves them as drafts in WordPress with configurable intervals.
Version: 1.2
Author: fk
*/

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
                $post_url = get_edit_post_link($post_id);
                echo '<div class="notice notice-success is-dismissible">
                        <p><strong>Success:</strong> Article was successfully scraped and saved as a draft! 
                        <a href="' . esc_url($post_url) . '" target="_blank">Edit Draft</a></p>
                      </div>';

                // Send email notification to the admin
                $admin_email = get_option('admin_email');
                $subject = 'New Article Scraped: ' . get_the_title($post_id);
                $message = 'A new article has been scraped and saved as a draft. You can edit it here: ' . $post_url;
                wp_mail($admin_email, $subject, $message);
            } else {
                echo '<div class="notice notice-error is-dismissible">
                        <p><strong>Error:</strong> Could not scrape the article. Possible issues:</p>
                        <ul><li>Invalid URL</li><li>Wrong selector</li><li>Bot protection</li></ul>
                      </div>';
            }
        }

        if (isset($_POST['aas_save_settings'])) {
            $interval = isset($_POST['aas_interval']) ? sanitize_text_field($_POST['aas_interval']) : '';
            $url = isset($_POST['aas_url']) ? esc_url_raw($_POST['aas_url']) : '';
            $selector = isset($_POST['aas_selector']) ? sanitize_text_field($_POST['aas_selector']) : '';

            if (in_array($interval, ['every_5_minutes', 'hourly', 'daily', 'weekly']) && $url) {
                update_option('aas_scrape_interval', $interval);
                update_option('aas_scrape_url', $url);
                update_option('aas_scrape_selector', $selector);
                aas_reschedule_scraper_event();
                echo '<div class="notice notice-success is-dismissible"><p>Scraping settings saved.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Valid interval and URL required.</p></div>';
            }
        }

        $interval = get_option('aas_scrape_interval', '');
        $url = get_option('aas_scrape_url', '');
        $selector = get_option('aas_scrape_selector', '');
        ?>

        <h2>Settings</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Scraping Interval</th>
                    <td>
                        <select name="aas_interval" required>
                            <option value="">-- Select --</option>
                            <option value="every_5_minutes" <?php selected($interval, 'every_5_minutes'); ?>>Every 5 Minutes</option>
                            <option value="hourly" <?php selected($interval, 'hourly'); ?>>Hourly</option>
                            <option value="daily" <?php selected($interval, 'daily'); ?>>Daily</option>
                            <option value="weekly" <?php selected($interval, 'weekly'); ?>>Weekly</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Article URL</th>
                    <td><input type="url" name="aas_url" value="<?php echo esc_attr($url); ?>" required style="width: 400px;"></td>
                </tr>
                <tr>
                    <th>Content Selector (optional)</th>
                    <td><input type="text" name="aas_selector" value="<?php echo esc_attr($selector); ?>" placeholder=".post-content or #main" style="width: 400px;"></td>
                </tr>
            </table>
            <?php submit_button('Save Settings', 'primary', 'aas_save_settings'); ?>
        </form>

        <h2>Manual Scrape</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Article URL</th>
                    <td><input type="url" name="aas_scrape_url" required style="width: 400px;"></td>
                </tr>
                <tr>
                    <th>Content Selector (optional)</th>
                    <td><input type="text" name="aas_selector" placeholder=".post-content or #main" style="width: 400px;"></td>
                </tr>
            </table>
            <?php submit_button('Fetch Article'); ?>
        </form>
    </div>
    <?php
}

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

    $title = 'No Title Found';
    $titleNodes = $dom->getElementsByTagName('title');
    if ($titleNodes->length > 0) {
        $title = $titleNodes->item(0)->nodeValue;
    }

    $xpath = new DOMXPath($dom);
    $contentNode = null;

    if ($selector) {
        if (strpos($selector, '#') === 0) {
            $id = substr($selector, 1);
            $nodes = $xpath->query("//*[@id='$id']");
        } elseif (strpos($selector, '.') === 0) {
            $class = substr($selector, 1);
            $nodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $class ')]");
        } else {
            $nodes = $xpath->query("//$selector");
        }
        if ($nodes->length > 0) $contentNode = $nodes->item(0);
    }

    if (!$contentNode) {
        foreach (['article', 'main'] as $tag) {
            $nodes = $xpath->query("//$tag");
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

    $post_data = [
        'post_title'   => wp_strip_all_tags($title),
        'post_content' => $content,
        'post_status'  => 'draft',
        'post_author'  => get_current_user_id(),
    ];

    $post_id = wp_insert_post($post_data);
    return (!is_wp_error($post_id)) ? $post_id : false;
}

add_filter('cron_schedules', 'aas_cron_schedules');
function aas_cron_schedules($schedules)
{
    $schedules['every_5_minutes'] = [
        'interval' => 300,
        'display'  => 'Every 5 Minutes',
    ];
    $schedules['hourly'] = [
        'interval' => 3600,
        'display'  => 'Hourly',
    ];
    $schedules['daily'] = [
        'interval' => 86400,
        'display'  => 'Daily',
    ];
    $schedules['weekly'] = [
        'interval' => 604800,
        'display'  => 'Weekly',
    ];
    return $schedules;
}

add_action('wp', 'aas_schedule_cron_job');
function aas_schedule_cron_job()
{
    $interval = get_option('aas_scrape_interval');
    if ($interval && !wp_next_scheduled('aas_scrape_cron_event')) {
        wp_schedule_event(time(), $interval, 'aas_scrape_cron_event');
    }
}

add_action('aas_scrape_cron_event', 'aas_run_scheduled_scrape');
function aas_run_scheduled_scrape()
{
    $url = get_option('aas_scrape_url', '');
    $selector = get_option('aas_scrape_selector', '');
    if ($url) {
        aas_scrape_single_article($url, $selector);
    }
}

function aas_reschedule_scraper_event()
{
    wp_clear_scheduled_hook('aas_scrape_cron_event');
    $interval = get_option('aas_scrape_interval');
    if ($interval) {
        wp_schedule_event(time(), $interval, 'aas_scrape_cron_event');
    }
}

register_deactivation_hook(__FILE__, 'aas_deactivate_cron');
function aas_deactivate_cron()
{
    wp_clear_scheduled_hook('aas_scrape_cron_event');
}
?>
