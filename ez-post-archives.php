<?php

/**
 * Plugin Name: EZ Post Archives
 * Description: Allows theme developers to easily add custom post type archives to their theme
 * Version: 1.0.0
 * Author: Jonathan Cowher
 * License: GPL2
 */
 
/**
 Copyright 2011  Jonathan Cowher (email: jonathan@jonathancowher.com)
 
 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>
 */
 
 
add_action('init', array('EZ_Post_Archives', 'generate_rewrite_rules'));
add_action('template_redirect', array('EZ_Post_Archives', 'select_template'));
add_action('pre_get_posts', array('EZ_Post_Archives', 'alter_query'));

add_filter('body_class', array('EZ_Post_Archives', 'add_body_classes'));
add_filter('wp_title', array('EZ_Post_Archives', 'filter_wp_title'), 11, 3);

register_activation_hook(__FILE__, array('EZ_Post_Archives', 'activate'));

class EZ_Post_Archives
{
    /** 
     * the plugin version
     * @static
     */
    static $version = '1.0.0';
    
    /**
     * Runs background tasks when plugin is activated
     * @return null
     */
    function activate()
    {
        flush_rewrite_rules(false);
    }
    
    /**
     * Creates an unordered list of archive links
     * @var mixed $args an array or querstring of arguments
     * @return null
     */
    function get($args = array())
    {
        global $post, $wp_query;
        
        $defaults = array(
            'post_type' => '',
            'type' => 'yearly',
            'limit' => 12,
            'year_format' => '<a href="{{link}}">{{year}}</a>',
            'month_format' => '<a href="{{link}}">{{month}} {{year}}</a>',
            'month_name_format' => 'M'
        );
        $limit = 0;
        $args = wp_parse_args($args, $defaults);
        
        if (empty($args['post_type'])) trigger_error('$post_type is required', E_USER_ERROR);
        
        $archives = array();
        $myposts = new WP_Query(array(
            'post_type' => $args['post_type'],
            'posts_per_page' => -1
        ));
        
        while ($myposts->have_posts()) : $myposts->the_post();
            if (isset($archives[get_the_time('Y')][get_the_time('n')])) :
                $archives[get_the_time('Y')][get_the_time('n')] = 1;
            else :
                $archives[get_the_time('Y')][get_the_time('n')] += 1;
            endif;
        endwhile;
        
        wp_reset_postdata();
        
        foreach ($archives as $year => $archive) :
            $yearLink = home_url() . '/archive/' . $args['post_type'] . '/' . $year . '/';
            $yearFormat = str_replace(array('{{year}}', '{{link}}'), array($year, $yearLink), $args['year_format']);
             
            if ($args['type'] == 'yearly') :
                
                echo '<li' . (get_query_var('year') == $year ? ' class="current"' : '') . '>' . $yearFormat . '</li>';
                
                // make sure we haven't exceeded user-specified limit
                if ($args['limit'] > 0) :
                    $limit += 1;
                    if ($limit == $args['limit']) :
                        break;
                    endif;
                endif;
            else :
                echo '<li' . (get_query_var('year') == $year ? ' class="current"' : '') . '>' . $yearFormat;
                
                echo '<ul class="months">';
                
                foreach ($archive as $month => $count) :
                    $monthLink = home_url() . '/archive/' . $args['post_type'] . '/' . $year . '/' . ($month < 10 ? '0' . $month : $month);
                    $monthName = date($args['month_name_format'], mktime(0, 0, 0, $month, 1));
                    $monthFormat = str_replace(array('{{link}}', '{{month}}', '{{year}}'), array($monthLink, $monthName, $year), $args['month_format']);
                    
                    echo '<li' . (get_query_var('year') == $year && get_query_var('monthnum') == $month ? ' class="current"' : '') . '>' . $monthFormat . '</li>';
                    
                    // make sure we haven't exceeded user-specified limit
                    if ($args['limit'] > 0) :
                        $limit += 1;
                        if ($limit == $args['limit']) :
                            break;
                        endif;
                    endif;
                endforeach;
                
                echo '</ul>';
                echo '</li>';
            endif;
        endforeach;
    }
    
    /**
     * Outputs page title for archive pages
     * @var mixed $args
     * @return null
     */
    function the_title()
    {
        global $wp_query;
        
        $pt = get_post_type_object(get_query_var('post_type'));
        
        if (get_query_var('monthnum')) :
            echo $pt->labels->singular_name . ' Archives From ' . date('F Y', mktime(0, 0, 0, get_query_var('monthnum'), 1, get_query_var('year')));
        else :
            echo $pt->labels->singular_name . ' Archives From ' . get_query_var('year');
        endif;
    }
    
    /**
     * Generates the necessary rewrite rules for our archives
     * @return null
     */
    function generate_rewrite_rules()
    {
        add_rewrite_tag('%yr%','([^&]+)');
        add_rewrite_tag('%mo%','([^&]+)');
        add_rewrite_tag('%templatename%','([^&]+)');
        
        add_rewrite_rule('^archive/([^/]*)/([\d]*)/([\d]*)/page/([\d]*)/?', 'index.php?post_type=$matches[1]&templatename=archive-$matches[1].php&yr=$matches[2]&mo=$matches[3]&paged=$matches[4]', 'top');
        add_rewrite_rule('^archive/([^/]*)/([\d]*)/page/([\d]*)/?', 'index.php?post_type=$matches[1]&templatename=archive-$matches[1].php&yr=$matches[2]&paged=$matches[3]', 'top');
        add_rewrite_rule('^archive/([^/]*)/([\d]*)/([\d]*)/?', 'index.php?post_type=$matches[1]&templatename=archive-$matches[1].php&yr=$matches[2]&mo=$matches[3]', 'top');
        add_rewrite_rule('^archive/([^/]*)/([\d]*)/?', 'index.php?post_type=$matches[1]&templatename=archive-$matches[1].php&yr=$matches[2]', 'top');
    }
    
    /**
     * Selects the appropriate template
     * @return null
     */
    function select_template()
    {
        global $wp_query;
        
        $templateName = get_query_var('templatename');
        
        if (! $templateName) return;
        
        if (file_exists(TEMPLATEPATH . '/' . $templateName)) :
            include TEMPLATEPATH . '/' . $templateName;
            exit;
        elseif (file_exists(TEMPLATEPATH . '/archive.php')) :
            include TEMPLATEPATH . '/archive.php';
            exit;
        endif;
    }
    
    /**
     * Alters the query for our special template pages
     * @return null
     */
    function alter_query($query)
    {
        $vars = $query->query_vars;
        
        if (empty($vars['templatename'])) return;
        
        $query->set('monthnum', empty($vars['mo']) ? null : $vars['mo']);
        $query->set('year', empty($vars['yr']) ? null : $vars['yr']);
        $query->set('is_post_type_archive', 1);
    }
    
    /**
     * Add custom body classes to the <body> tag
     * @return array
     */
    function add_body_classes($classes)
    {
        global $wp_query;
        
        $vars = $wp_query->query_vars;
        
        if (empty($vars['templatename'])) return $classes; 
        
        $classes[] = 'ez-post-archives';
        $classes[] = 'ez-post-archives-' . $vars['post_type'];
        
        return $classes;
    }
    
    /**
     * Filter the <title> tag for archive pages
     * @var string $title the current page title
     * @return string
     */
    function filter_wp_title($title, $before, $sep)
    {
        global $wp_query;
        
        $vars = $wp_query->query_vars;
        
        if (empty($vars['templatename'])) return $title;
        
        $pt = get_post_type_object(get_query_var('post_type'));
        
        if (get_query_var('monthnum')) :
            return $pt->labels->singular_name . ' Archives From ' . date('F Y', mktime(0, 0, 0, get_query_var('monthnum'), 1, get_query_var('year'))) . ' - ' . get_bloginfo('name');
        else :
            return $pt->labels->singular_name . ' Archives From ' . get_query_var('year') . ' - ' . get_bloginfo('name');
        endif;
    }
}