<?php

// Function to scrape a single article
function aas_scrape_single_article($url)
{
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return false;
    }

    $html = wp_remote_retrieve_body($response);
    if (empty($html)) {
        return false;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    // Remove header, footer, nav, and aside
    $tagsToRemove = ['header', 'footer', 'nav', 'aside'];
    foreach ($tagsToRemove as $tag) {
        $elements = $dom->getElementsByTagName($tag);
        while ($elements->length > 0) {
            $element = $elements->item(0);
            $element->parentNode->removeChild($element);
        }
    }

    // Get the title
    $titleNodes = $dom->getElementsByTagName('title');
    $title = ($titleNodes->length > 0) ? $titleNodes->item(0)->nodeValue : 'No Title Found';

    // XPath to find the article content
    $xpath = new DOMXPath($dom);
    $contentNode = null;

    // Try <article> tag
    $nodes = $xpath->query('//article');
    if ($nodes->length > 0) {
        $contentNode = $nodes->item(0);
    }

    // Try <main> tag
    if (!$contentNode) {
        $nodes = $xpath->query('//main');
        if ($nodes->length > 0) {
            $contentNode = $nodes->item(0);
        }
    }

    // Try biggest <div> with most text
    if (!$contentNode) {
        $divs = $xpath->query('//div');
        $maxTextLength = 0;
        foreach ($divs as $div) {
            $text = trim($div->textContent);
            if (strlen($text) > $maxTextLength) {
                $maxTextLength = strlen($text);
                $contentNode = $div;
            }
        }
    }

    if (!$contentNode) {
        return false;
    }

    $content = $dom->saveHTML($contentNode);

    // Save as draft post
    $post_data = [
        'post_title'   => wp_strip_all_tags($title),
        'post_content' => $content,
        'post_status'  => 'draft',
        'post_author'  => get_current_user_id(),
    ];

    $post_id = wp_insert_post($post_data);

    return (!is_wp_error($post_id)) ? $post_id : false;
}