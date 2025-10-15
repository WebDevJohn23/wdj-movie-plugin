<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Insert movie if not already present for this theater.
 * Requires wdj_mp_table() in includes/db.php.
 */
function wdj_mp_insert_movie_if_new( array $row, string $theater_value ) : bool {
    global $wpdb;
    $table = wdj_mp_table();

    $code         = sanitize_text_field( $row['code'] ?? '' );
    $featureTitle = sanitize_text_field( $row['featureTitle'] ?? '' );
    if ( $code === '' || $featureTitle === '' ) return false;

    $exists = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE code = %s AND theater = %s",
            $code, $theater_value
        )
    );
    if ( $exists ) return false;

    $wpdb->insert(
        $table,
        array(
            'code'         => $code,
            'featureTitle' => $featureTitle,
            'posterSrc'    => esc_url_raw( $row['posterSrc'] ?? '' ),
            'moviePicture' => esc_url_raw( $row['moviePicture'] ?? '' ),
            'url'          => esc_url_raw( $row['url'] ?? '' ),
            'weight'       => 0,
            'dateStarted'  => rtrim( sanitize_text_field( $row['dateStarted'] ?? '' ), 'T00:00:00' ),
            'theater'      => $theater_value,
            'watched'      => 0,
            'misc'         => null,
        ),
        array( '%s','%s','%s','%s','%s','%d','%s','%s','%d','%s' )
    );

    return true;
}

/**
 * Build theater display label from URL like:
 * https://www.regmovies.com/theatres/regal-cielo-vista-0765
 */
function wdj_mp_theater_label_from_url( string $url ) : string {
    $path = wp_parse_url( $url, PHP_URL_PATH );
    $slug = trim( basename( $path ) );              // regal-cielo-vista-0765
    $slug = preg_replace( '~^regal\-~i', '', $slug ); // cielo-vista-0765
    if ( preg_match( '~^(.*?)-(\d{3,5})$~', $slug, $m ) ) {
        $name = $m[1];
    } else {
        $name = $slug;
    }
    $name = str_replace( array( '-san-antonio', '-' ), array( ' ', ' ' ), $name );
    return ucwords( trim( $name ) );                // Cielo Vista
}

/**
 * Unwrap Next.js image proxy to real poster URL.
 */
function wdj_mp_unwrap_next_image( string $src ) : string {
    $q = parse_url( $src, PHP_URL_QUERY );
    if ( ! $q ) return $src;
    parse_str( $q, $qs );
    return isset( $qs['url'] ) ? urldecode( $qs['url'] ) : $src;
}

/**
 * Choose earliest date label like "Thu Oct 16" and return YYYY-MM-DD.
 */
function wdj_mp_best_date( array $labels ) : string {
    if ( ! $labels ) return '';
    $year = (int) current_time( 'Y' );
    $ts   = array();
    foreach ( $labels as $l ) {
        if ( preg_match( '~[A-Za-z]{3}\s+([A-Za-z]{3})\s+(\d{1,2})~', $l, $m ) ) {
            $t = strtotime( sprintf( '%s %02d %d', $m[1], (int) $m[2], $year ) );
            if ( $t ) $ts[] = $t;
        }
    }
    if ( ! $ts ) return '';
    sort( $ts );
    return gmdate( 'Y-m-d', $ts[0] );
}

/**
 * Parse one theater page HTML into movie rows.
 */
function wdj_mp_parse_cards( string $html ) : array {
    libxml_use_internal_errors( true );
    $dom = new DOMDocument();
    $dom->loadHTML( $html );
    $xp  = new DOMXPath( $dom );

    // Movie link with <h4> inside, href starts with /movies/
    $links = $xp->query( '//a[starts-with(@href,"/movies/")][h4]' );
    $items = array();

    foreach ( $links as $a ) {
        /** @var DOMElement $a */
        $href  = $a->getAttribute( 'href' );                 // /movies/slug
        $title = trim( $xp->evaluate( 'string(./h4)', $a ) );

        // poster in same card block
        $img = $xp->evaluate('(./ancestor::div[contains(@class,"exzlyda")][1]//img)[1]', $a)->item(0);
        $poster = '';
        if ($img instanceof DOMElement) {
            $poster = wdj_mp_get_poster_url($img); // direct CDN poster URL
        }


        // Date buttons inside same card
        $btns = $xp->query( '(./ancestor::div[contains(@class,"exzlyda")][1]//button)[contains(@id,"preshow") or contains(@class,"exzlyda30")]' , $a );
        $labels = array();
        foreach ( $btns as $b ) {
            $t = trim( preg_replace( '/\s+/', ' ', $b->textContent ) );
            if ( $t ) $labels[] = $t;                         // Thu Oct 16
        }

        // Code from slug tail
        $slug = basename( $href );                            // good-fortune-ho00018864
        if ( preg_match( '~(ho\d+)$~i', $slug, $m ) ) {
            $code = strtoupper( $m[1] );                     // HO00018864
        } else {
            $code = strtoupper( preg_replace( '~[^A-Z0-9]+~', '', $slug ) );
        }

        $items[] = array(
            'code'         => $code,
            'featureTitle' => $title,
            'posterSrc'    => $poster,
            'url'          => 'https://www.regmovies.com' . $href,
            'dateStarted'  => wdj_mp_best_date( $labels ),
            'moviePicture' => $poster,
        );
    }

    libxml_clear_errors();
    return $items;
}

// Fetch Regal homepage once to reuse cookies and bypass basic bot filters
function wdj_mp_get_regal_cookies() : array {
    $res = wp_remote_get('https://www.regmovies.com/', array(
        'timeout' => 20,
        'headers' => array(
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.8',
        ),
    ));
    if ( is_wp_error($res) ) return array();
    $cookies = wp_remote_retrieve_cookies($res);
    return is_array($cookies) ? $cookies : array();
}



/**
 * Fetch a Regal theater page and insert parsed movies.
 */
function wdj_mp_refresh_from_theater_page( string $theater_url ) : void {

    $res = wp_remote_get( $theater_url, array(
        'timeout' => 25,
        'headers' => array(
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:118.0) Gecko/20100101 Firefox/118.0',
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.8',
            'Referer'         => 'https://www.regmovies.com/',
        ),
    ) );
    if ( is_wp_error( $res ) ) return;

    $html = wp_remote_retrieve_body( $res );
    if ( ! $html ) return;

    // Debug snapshot if needed
    // file_put_contents( WP_CONTENT_DIR . '/debug_regal.html', $html );

    $theater_value = wdj_mp_theater_label_from_url( $theater_url );
    $items = wdj_mp_parse_cards( $html );

    foreach ($items as $row) {
        if (is_admin()) {
            $row['posterSrc']    = wdj_mp_sideload_poster( wdj_mp_unwrap_next_image($row['posterSrc'] ?? '') );
            $row['moviePicture'] = $row['posterSrc'];
        }
        wdj_mp_insert_movie_if_new($row, $theater_value);
    }

}

/**
 * Query movies by watched status.
 * 0 not watched, 1 watched, 2 not interested.
 */
function wdj_mp_get_movies_by_status( int $status ) : array {
    global $wpdb;
    $table = wdj_mp_table();
    $sql = "
        SELECT
            MIN(id) AS id,
            code,
            featureTitle,
            posterSrc,
            url,
            MAX(dateStarted) AS dateStarted,
            GROUP_CONCAT(theater) AS theaters
        FROM {$table}
        WHERE watched = %d
        GROUP BY featureTitle, code, posterSrc, url
        ORDER BY dateStarted DESC, featureTitle ASC
    ";
    return $wpdb->get_results( $wpdb->prepare( $sql, $status ) );
}


// Absolute URL helper
function wdj_mp_abs_url(string $url) : string {
    if ($url === '') return '';
    if (strpos($url, '//') === 0)  return 'https:' . $url;
    if ($url[0] === '/')           return 'https://www.regmovies.com' . $url;
    return $url;
}

// Extract real poster URL from <img> with Next.js proxy
function wdj_mp_img_poster_url(DOMElement $img) : string {
    $srcset = $img->getAttribute('srcset');
    $src    = $img->getAttribute('src');

    $pick = $srcset ?: $src;
    $pick = trim(explode(' ', $pick)[0]);                    // first candidate
    $pick = wdj_mp_abs_url($pick);                           // ensure absolute

    // unwrap /_next/image?url=ENCODED&...
    $q = parse_url($pick, PHP_URL_QUERY);
    if ($q) {
        parse_str($q, $qs);
        if (!empty($qs['url'])) return urldecode($qs['url']);
    }
    return $pick;
}

// Load media libs if needed
function wdj_mp_maybe_load_media_libs() : bool {
    if ( function_exists('media_sideload_image') ) return true;
    $base = trailingslashit( ABSPATH ) . 'wp-admin/includes/';
    foreach ( array('media.php','file.php','image.php') as $f ) {
        $p = $base . $f;
        if ( file_exists($p) ) include_once $p; else return false;
    }
    return function_exists('media_sideload_image');
}

/**
 * Sideload a poster URL into Media Library. Returns local URL.
 * Reuses an attachment if already downloaded for this source URL.
 */
function wdj_mp_sideload_poster( string $url ) : string {
    if ( $url === '' ) return '';
    global $wpdb;

    // reuse if we saved this exact source before
    $aid = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wdj_source_url' AND meta_value = %s LIMIT 1",
        $url
    ) );
    if ( $aid ) {
        $u = wp_get_attachment_url( $aid );
        return $u ?: $url;
    }

    if ( ! wdj_mp_maybe_load_media_libs() ) return $url;

    // no parent post
    $att_id = media_sideload_image( $url, 0, null, 'id' );
    if ( is_wp_error( $att_id ) ) return $url;

    add_post_meta( $att_id, '_wdj_source_url', $url, true );
    $local = wp_get_attachment_url( $att_id );
    return $local ?: $url;
}
// Extract real poster URL from Regal's Next.js <img> tag
function wdj_mp_get_poster_url( DOMElement $img ) : string {
    $srcset = $img->getAttribute('srcset');
    $src    = $img->getAttribute('src');
    $raw    = trim(explode(' ', $srcset ?: $src)[0]);

    // Make absolute URL if needed
    if ( strpos($raw, '//') === 0 ) $raw = 'https:' . $raw;
    elseif ( strpos($raw, '/') === 0 ) $raw = 'https://www.regmovies.com' . $raw;

    // Unwrap Next.js proxy e.g. /_next/image?url=ENCODED&...
    $q = parse_url($raw, PHP_URL_QUERY);
    if ($q) {
        parse_str($q, $qs);
        if (!empty($qs['url'])) return urldecode($qs['url']);
    }
    return $raw;
}
