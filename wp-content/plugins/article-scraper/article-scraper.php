<?php
/*
Plugin Name: Article Scraper
Description: Scrapes a URL at a user‑defined minute interval and saves it as a draft. Also provides a shortcode to output scraped data on the front end.
Version:     1.3
Author:      fk
*/

// ── 1) Register the admin menu page ───────────────────────────────────────
add_action('admin_menu', function() {
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

// ── 2) Render the settings & manual‑fetch form ────────────────────────────
function aas_article_scraper_page() {
    $url      = get_option('aas_url', '');
    $selector = get_option('aas_selector', '');
    $mins     = intval(get_option('aas_interval_minutes', 60));

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['aas_save_settings'])) {
            $new_url      = esc_url_raw($_POST['aas_url']);
            $new_selector = sanitize_text_field($_POST['aas_selector']);
            $new_mins     = max(1, intval($_POST['aas_interval_minutes']));

            update_option('aas_url', $new_url);
            update_option('aas_selector', $new_selector);
            update_option('aas_interval_minutes', $new_mins);

            aas_reschedule_scraper_event();

            echo '<div class="notice notice-success is-dismissible">'
               . '<p>Settings saved. Next run in ' . esc_html($new_mins) . ' minute(s).</p>'
               . '</div>';

            $url      = $new_url;
            $selector = $new_selector;
            $mins     = $new_mins;
        }
        if (isset($_POST['aas_scrape_now'])) {
            $post_id = aas_scrape_single_article($url, $selector);
            if ($post_id) {
                echo '<div class="notice notice-success is-dismissible">'
                   . '<p>Fetched and created draft! <a href="' . get_edit_post_link($post_id) . '">Edit it</a>.</p>'
                   . '</div>';
            } else {
                echo '<div class="notice notice-error is-dismissible">'
                   . '<p>Scrape failed. Check URL/selector.</p>'
                   . '</div>';
            }
        }
    }

    ?>
    <div class="wrap">
      <h1>Article Scraper Settings</h1>
      <form method="post">
        <table class="form-table">
          <tr>
            <th scope="row">Article URL</th>
            <td>
              <input type="url" name="aas_url" value="<?php echo esc_attr($url); ?>"
                     style="width:400px" required />
            </td>
          </tr>
          <tr>
            <th scope="row">Content Selector (optional)</th>
            <td>
              <input type="text" name="aas_selector" value="<?php echo esc_attr($selector); ?>"
                     placeholder=".post-content or #main" style="width:400px" />
            </td>
          </tr>
          <tr>
            <th scope="row">Fetch Every (minutes)</th>
            <td>
              <input type="number" name="aas_interval_minutes"
                     value="<?php echo esc_attr($mins); ?>"
                     min="1" style="width:100px" /> minutes
            </td>
          </tr>
        </table>
        <?php submit_button('Save Settings', 'primary', 'aas_save_settings'); ?>
        <?php submit_button('Fetch Now', 'secondary', 'aas_scrape_now'); ?>
      </form>
    </div>
    <?php
}

// ── 3) Dynamic Cron Schedule ──────────────────────────────────────────────
add_filter('cron_schedules', function($schedules) {
    $mins = max(1, intval(get_option('aas_interval_minutes', 60)));
    $secs = $mins * MINUTE_IN_SECONDS;
    $schedules['aas_user_minute_interval'] = [
        'interval' => $secs,
        'display'  => sprintf('Every %d minute(s)', $mins),
    ];
    return $schedules;
});

// ── 4) Activation & Deactivation ─────────────────────────────────────────
register_activation_hook(__FILE__, 'aas_activate');
function aas_activate() {
    if (! get_option('aas_interval_minutes')) {
        update_option('aas_interval_minutes', 60);
    }
    aas_reschedule_scraper_event();
}
register_deactivation_hook(__FILE__, 'aas_deactivate');
function aas_deactivate() {
    wp_clear_scheduled_hook('aas_user_minute_interval_event');
}

// ── 5) Schedule Cron Event ───────────────────────────────────────────────
function aas_reschedule_scraper_event() {
    wp_clear_scheduled_hook('aas_user_minute_interval_event');
    if (! wp_next_scheduled('aas_user_minute_interval_event')) {
        wp_schedule_event(time(), 'aas_user_minute_interval', 'aas_user_minute_interval_event');
    }
}

// ── 6) Cron Hook ─────────────────────────────────────────────────────────
add_action('aas_user_minute_interval_event', 'aas_run_scheduled_scraper');
function aas_run_scheduled_scraper() {
    $url      = get_option('aas_url');
    $selector = get_option('aas_selector', '');
    if ($url) {
        aas_scrape_single_article($url, $selector);
    }
}

// ── 7) Scraper & Draft Creator ────────────────────────────────────────────
function aas_scrape_single_article($url, $selector = null) {
    $data = aas_get_scraped_data($url, $selector);
    if (! $data) return false;

    $post_id = wp_insert_post([
        'post_title'   => wp_strip_all_tags($data['title']),
        'post_content' => $data['content'],
        'post_status'  => 'draft',
        'post_author'  => get_current_user_id(),
    ]);
    if (is_wp_error($post_id)) return false;

    wp_mail(
        get_option('admin_email'),
        'New Article Scraped',
        "Title: {$data['title']}\nURL: {$url}\nEdit: " . get_edit_post_link($post_id)
    );

    return $post_id;
}

// ── 8) Scraper Utility ────────────────────────────────────────────────────
function aas_get_scraped_data($url, $selector = null) {
    $response = wp_remote_get($url);
    if (is_wp_error($response)) return false;
    $html = wp_remote_retrieve_body($response);
    if (empty($html)) return false;

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    foreach (['header','footer','nav','aside'] as $tag) {
        $els = $dom->getElementsByTagName($tag);
        while ($els->length) {
            $els->item(0)->parentNode->removeChild($els->item(0));
        }
    }

    $title = 'No Title Found';
    $tNodes = $dom->getElementsByTagName('title'); if ($tNodes->length) {
        $title = $tNodes->item(0)->nodeValue;
    }

    $xpath = new DOMXPath($dom);
    $contentNode = null;
    if ($selector) {
        $sel = trim($selector);
        if (strpos($sel,'#')===0) {
            $nodes = $xpath->query("//*[@id='".substr($sel,1)."']");
        } elseif (strpos($sel,'.')===0) {
            $cls = substr($sel,1);
            $nodes = $xpath->query("//*[contains(concat(' ',normalize-space(@class),' '),' $cls ')]");
        } else {
            $nodes = $xpath->query("//{$sel}");
        }
        if ($nodes && $nodes->length) $contentNode = $nodes->item(0);
    }
    if (! $contentNode) {
        foreach (['article','main'] as $tag) {
            $nodes = $xpath->query("//{$tag}");
            if ($nodes->length) { $contentNode = $nodes->item(0); break; }
        }
    }
    if (! $contentNode) {
        $divs = $xpath->query('//div'); $max=0;
        foreach ($divs as $div) {
            $len = strlen(trim($div->textContent));
            if ($len>$max) { $max=$len; $contentNode=$div; }
        }
    }
    if (! $contentNode) return false;

    return [
        'title'   => $title,
        'content' => $dom->saveHTML($contentNode),
    ];
}

// ── 9) Shortcode for Front‑End Display ───────────────────────────────────
add_shortcode('scrape', 'aas_shortcode_scrape');
function aas_shortcode_scrape($atts) {
    $atts = shortcode_atts(['url'=>'','selector'=>''], $atts);
    if (empty($atts['url'])) return '';
    $data = aas_get_scraped_data($atts['url'], $atts['selector']);
    if (! $data) return '';
    return '<h2>' . esc_html($data['title']) . '</h2>' . $data['content'];
}
?>
