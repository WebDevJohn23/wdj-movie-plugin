<?php
if ( ! defined('ABSPATH') ) exit;

/** Ajax: update watched status for front end */
add_action('wp_ajax_wdj_mp_update_watched', 'wdj_mp_ajax_update_watched');
add_action('wp_ajax_nopriv_wdj_mp_update_watched', 'wdj_mp_ajax_update_watched');
function wdj_mp_ajax_update_watched() {
    check_ajax_referer('wdj_mp_nonce', 'nonce');
    global $wpdb;
    $table   = wdj_mp_table();
    $code    = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';
    $watched = isset($_POST['watched']) ? intval($_POST['watched']) : 0;
    if ( $code === '' ) wp_send_json_error('missing code');
    $wpdb->query( $wpdb->prepare("UPDATE {$table} SET watched = %d WHERE code = %s", $watched, $code) );
    wp_send_json_success('ok');
}

/** Shortcode: [wdj_movies] or [wdj_movies status="0|1|2"] */
add_shortcode('wdj_movies', function( $atts ){
    $atts = shortcode_atts( array( 'status' => '' ), $atts );
    $nonce = wp_create_nonce('wdj_mp_nonce');
    $ajax  = esc_url( admin_url('admin-ajax.php') );

    ob_start();
    ?>
    <style>
        .wdj-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: orangered;
            color: #000;
            text-align: center;
            padding: 4px 0;
            font-weight: 600;
            border-bottom: 1px solid #000;}
        .wdj-header{padding:1px;background:orangered;color:black;width:100%;text-align:center;margin:24px 0 8px}
        table.wdj{width:100%}
        table.wdj td{text-align:center;font-size:18px}
        .wdj-image{box-shadow:0 4px 8px rgba(0,0,0,.2),0 6px 20px rgba(0,0,0,.19)}
        .flip-box{margin:0 auto;background:transparent;width:175px;height:250px;perspective:1000px}
        .flip-box-inner{position:relative;width:100%;height:100%;text-align:center;transition:transform .8s;transform-style:preserve-3d}
        .flip-box:hover .flip-box-inner{transform:rotateY(180deg)}
        .flip-box-front,.flip-box-back{position:absolute;width:100%;height:100%;backface-visibility:hidden}
        .flip-box-front{background:transparent;color:black}
        .flip-box-back{background:black;color:white;transform:rotateY(180deg);text-shadow:1px 1px 2px white,0 0 25px blue,0 0 5px darkblue}
    </style>
    <script>
        document.addEventListener('DOMContentLoaded',function(){
            const ajax="<?php echo $ajax; ?>";
            const nonce="<?php echo esc_js($nonce); ?>";
            window.wdjLoaddata=function(code,num){
                const row=document.getElementById(code);
                if(!row) return;
                const fd=new FormData();
                fd.append('action','wdj_mp_update_watched');
                fd.append('code',code);
                fd.append('watched',num);
                fd.append('nonce',nonce);
                fetch(ajax,{method:'POST',body:fd,credentials:'same-origin'})
                    .then(r=>r.json())
                    .then(j=>{ if(j && j.success){ row.remove(); } });
            };
        });
    </script>
    <?php

    $sections = array(
        array(0,'In Theaters'),
        array(1,'Watched Movies'),
        array(2,'Not Interested'),
    );

    if ( $atts['status'] !== '' ) {
        $s = intval($atts['status']);
        $sections = array( array($s, $s===0?'In Theaters':($s===1?'Watched Movies':'Not Interested')) );
    }

    foreach ( $sections as $spec ) {
        list($status,$heading) = $spec;
        echo '<div class="wdj-header"><h2>'.esc_html($heading).'</h2></div>';
        wdj_mp_render_shortcode_section( $status );
    }

    return ob_get_clean();
});

/** Render a section for the shortcode */
function wdj_mp_render_shortcode_section( int $status ) : void {
    $rows = wdj_mp_get_movies_by_status( $status );
    if ( ! $rows ) { echo '<p>No movies found.</p>'; return; }

    $number = 1;
    echo '<table class="wdj">';
    foreach ( $rows as $row ) {
        $code   = esc_attr($row->code);
        $title  = esc_html( html_entity_decode( (string)$row->featureTitle, ENT_QUOTES ) );
        $poster = esc_url( (string)$row->posterSrc );
        if ($poster==''){
            $poster = 'https://www.nyfa.edu/wp-content/uploads/2022/11/Blank-Movie-Poster1.jpg';
        }
        $url    = esc_url( (string)$row->url );
        $theaters = explode(',', (string)$row->theaters);
        $date   = $row->dateStarted ? esc_html( date_i18n('m/d/Y', strtotime($row->dateStarted)) ) : '';
        $bg     = ($number % 2 === 0) ? 'ghostwhite' : 'floralwhite';

        echo '<tr style="background:#fff;border-collapse:collapse">';
        echo '  <td>';
        echo '    <table style="width:100%;border-spacing:10px 20px">';
        echo '      <tr id="'.$code.'" style="background:#fff;border-collapse:collapse">';
        echo '        <td style="width:50%;text-align:center">';
        echo '          <div class="flip-box"><div class="flip-box-inner">';
        echo '            <div class="flip-box-front"><img class="wdj-image" src="'.$poster.'" style="height:250px" /></div>';
        echo '            <div class="flip-box-back"><br/>';

        if ($status === 0) {
            echo '  <div onclick="wdjLoaddata(\''.$code.'\',\'1\')"><h2>Watched</h2></div><br/>';
            echo '  <div onclick="wdjLoaddata(\''.$code.'\',\'2\')"><h2>Not Interested</h2></div>';
        } elseif ($status === 1) {
            echo '  <div onclick="wdjLoaddata(\''.$code.'\',\'0\')"><h2>Not Watched</h2></div><br/>';
            echo '  <div onclick="wdjLoaddata(\''.$code.'\',\'2\')"><h2>Not Interested</h2></div>';
        } else {
            echo '  <div onclick="wdjLoaddata(\''.$code.'\',\'1\')"><h2>Watched</h2></div><br/>';
            echo '  <div onclick="wdjLoaddata(\''.$code.'\',\'0\')"><h2>Not Watched</h2></div>';
        }

        echo '            </div></div></div>';
        echo '        </td>';
        echo '        <td class="wdj-image" style="width:50%;background-color:'.$bg.'">';
        echo '          <a href="'.$url.'">'.$title.'</a><br/><br/>';
        echo '          <div style="pointer-events:none">';
        echo              $date.'<br/><br/>';
        for ($i=0;$i<6;$i++){
            if (!empty($theaters[$i])) echo esc_html($theaters[$i]).'<br/>';
        }
        echo '          </div>';
        echo '        </td>';
        echo '      </tr>';
        echo '    </table>';
        echo '  </td>';
        echo '</tr>';

        $number++;
    }
    echo '</table>';
}
