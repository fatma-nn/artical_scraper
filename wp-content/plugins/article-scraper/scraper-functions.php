<?php

// Function to scrape a single article
function aas_scrape_single_article($url)
{
    // Fetch the content
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return false;
    }

    $html = wp_remote_retrieve_body($response);

    if (empty($html)) {
        return false;
    }

    // Use DOMDocument to scrape the article
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    // Get the title
    $titleNodes = $dom->getElementsByTagName('title');
    $title = ($titleNodes->length > 0) ? $titleNodes->item(0)->nodeValue : 'No Title Found';

    // Get the content
    $body = $dom->getElementsByTagName('body');
    $content = ($body->length > 0) ? $dom->saveHTML($body->item(0)) : '';

    if (empty(trim($content))) {
        return false;
    }

    // Insert post as a draft
    $post_data = [
        'post_title'   => wp_strip_all_tags($title),
        'post_content' => $content,
        'post_status'  => 'draft',
        'post_author'  => get_current_user_id(),
    ];

    $post_id = wp_insert_post($post_data);

    if (!is_wp_error($post_id)) {
        return $post_id;
    }

    return false;
}
