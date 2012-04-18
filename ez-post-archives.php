<?php

/**
 * Plugin Name: EZ Post Archives
 * Description: Allows theme developers to easily add custom post type and custom taxonomy archives to their theme
 * Version: 1.1
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
 
EZ_Post_Archives::init();

class EZ_Post_Archives
{
    /*--------------------------------------------------------------------------------------
     *
     * @var string $version
     * - the plugin version   
     *
     *--------------------------------------------------------------------------------------*/

    static $version = '1.1';
    
    /*--------------------------------------------------------------------------------------
     *
     * init()
     * - initialize the plugin   
     *
     *--------------------------------------------------------------------------------------*/
     
    function init()
    {
        $classname = get_called_class();
        
        add_action('init', array($classname, 'generate_rewrite_rules'));
        add_action('template_redirect', array($classname, 'select_template'), 10, 0);
        add_action('pre_get_posts', array($classname, 'alter_query'));
        
        add_filter('body_class', array($classname, 'add_body_classes'));
        add_filter('wp_title', array($classname, 'filter_wp_title'), 11, 3);
        
        register_activation_hook(__FILE__, array($classname, 'activate'));
    }
    
    /*--------------------------------------------------------------------------------------
     *
     * activate()
     * - runs background tasks when plugin is activated
     *
     *--------------------------------------------------------------------------------------*/

    function activate()
    {
        flush_rewrite_rules(false);
    }
    
    /*--------------------------------------------------------------------------------------
     *
     * get($args)
     * - creates an unordered list of archive links
     *
     * @param mixed $args an array or querstring of arguments
     *
     *--------------------------------------------------------------------------------------*/
    
    function get($args = array())
    {
        global $post, $wp_query;
        
        $defaults = array(
            'post_type' => '',
            'type' => 'yearly',
            'taxonomy' => null,
            'term' => null,
            'limit' => 12,
            'year_format' => '<a href="{{link}}">{{year}}</a>',
            'month_format' => '<a href="{{link}}">{{month}} {{year}}</a>',
            'month_name_format' => 'M'
        );
        $limit = 0;
        $args = wp_parse_args($args, $defaults);
        
        if (empty($args['post_type'])) trigger_error('$post_type is required', E_USER_ERROR);
        
        $archives = array();
        $query_args = array(
            'post_type' => $args['post_type'],
            'posts_per_page' => -1
        );
        
        if (! empty($args['taxonomy']) && ! empty($args['term'])) :
            $query_args = array_merge($query_args, array(
                'tax_query' => array(
                    array(
                        'taxonomy' => $args['taxonomy'],
                        'field' => 'slug',
                        'terms' => $args['term'],
                    ),
                ),
            ));
        endif;
        
        $myposts = new WP_Query($query_args);
        
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
            
            if (! empty($args['taxonomy']) && ! empty($args['term'])) :
                $yearLink = home_url() . '/archive/' . $args['post_type'] . '/' . $args['taxonomy'] . '/' . $args['term'] . '/' . $year . '/';
            endif;
            
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
                    
                    if (! empty($args['taxonomy']) && ! empty($args['term'])) :
                        $monthLink = home_url() . '/archive/' . $args['post_type'] . '/' . $args['taxonomy'] . '/' . $args['term'] . '/' . $year . '/' . ($month < 10 ? '0' . $month : $month);
                    endif;

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
    
    /*--------------------------------------------------------------------------------------
     *
     * the_title()
     * - outputs a user-friendly title for archive pages
     *
     *--------------------------------------------------------------------------------------*/
    
    function the_title()
    {
        global $wp_query;
        
        $pt = get_post_type_object(get_query_var('post_type'));
        
        if (get_query_var('taxonomy') && get_query_var('term')) :
            $term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
            echo $term->name;
        else :
            echo $pt->labels->singular_name;
        endif;
            
        if (get_query_var('monthnum')) :
            echo ' Archives From ' . date('F Y', mktime(0, 0, 0, get_query_var('monthnum'), 1, get_query_var('year')));
        else :
            echo ' Archives From ' . get_query_var('year');
        endif;
    }
    
    /*--------------------------------------------------------------------------------------
     *
     * generate_rewrite_rules()
     * - generates the necessary rewrite rules for archive templates 
     *
     *--------------------------------------------------------------------------------------*/

    function generate_rewrite_rules()
    {
        add_rewrite_tag('%yr%','([^&]+)');
        add_rewrite_tag('%mo%','([^&]+)');
        add_rewrite_tag('%templatename%','([^&]+)');
        
        // post type rewrites
        add_rewrite_rule('^archive/([^/]*)/([\d]*)/([\d]*)/page/([\d]*)/?$', 'index.php?post_type=$matches[1]&templatename=archive-$matches[1].php&yr=$matches[2]&mo=$matches[3]&paged=$matches[4]', 'top');
        add_rewrite_rule('^archive/([^/]*)/([\d]*)/page/([\d]*)/?$', 'index.php?post_type=$matches[1]&templatename=archive-$matches[1].php&yr=$matches[2]&paged=$matches[3]', 'top');
        add_rewrite_rule('^archive/([^/]*)/([\d]*)/([\d]*)/?$', 'index.php?post_type=$matches[1]&templatename=archive-$matches[1].php&yr=$matches[2]&mo=$matches[3]', 'top');
        add_rewrite_rule('^archive/([^/]*)/([\d]*)/?$', 'index.php?post_type=$matches[1]&templatename=archive-$matches[1].php&yr=$matches[2]', 'top');
        
        // taxonomy rewrites
        add_rewrite_rule('^archive/([^/]*)/([^/]*)/([^/]*)/([\d]*)/([\d]*)/page/([\d]*)/?$', 'index.php?post_type=$matches[1]&taxonomy=$matches[2]&term=$matches[3]&templatename=archive-$matches[1].php&yr=$matches[4]&mo=$matches[5]&paged=$matches[6]', 'top');
        add_rewrite_rule('^archive/([^/]*)/([^/]*)/([^/]*)/([\d]*)/page/([\d]*)/?$', 'index.php?post_type=$matches[1]&taxonomy=$matches[2]&term=$matches[3]&templatename=archive-$matches[1].php&yr=$matches[4]&paged=$matches[5]', 'top');
        add_rewrite_rule('^archive/([^/]*)/([^/]*)/([^/]*)/([\d]*)/([\d]*)/?$', 'index.php?post_type=$matches[1]&taxonomy=$matches[2]&term=$matches[3]&templatename=archive-$matches[1].php&yr=$matches[4]&mo=$matches[5]', 'top');
        add_rewrite_rule('^archive/([^/]*)/([^/]*)/([^/]*)/([\d]*)/?$', 'index.php?post_type=$matches[1]&taxonomy=$matches[2]&term=$matches[3]&templatename=archive-$matches[1].php&yr=$matches[4]', 'top');
    }
    
    /*--------------------------------------------------------------------------------------
     *
     * select_template()
     * - selects the appropriate theme template page
     *
     *--------------------------------------------------------------------------------------*/

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
        else :
            include TEMPLATEPATH . '/index.php';
            exit;
        endif;
    }
    
    /*--------------------------------------------------------------------------------------
     *
     * alter_query($query)
     * - alters the current query for use in theme template pages
     *
     * @param object $query the current query as provided by WordPress   
     *
     *--------------------------------------------------------------------------------------*/
    
    function alter_query($query)
    {
        $vars = $query->query_vars;
        
        if (empty($vars['templatename'])) return;
        
        $query->set('monthnum', empty($vars['mo']) ? null : $vars['mo']);
        $query->set('year', empty($vars['yr']) ? null : $vars['yr']);
        $query->set('is_post_type_archive', 1);
        
        if (isset($vars['taxonomy']) && isset($vars['term']))
            $query->set($vars['taxonomy'], $vars['term']);
    }
    
    /*--------------------------------------------------------------------------------------
     *
     * add_body_classes($classes)
     * - adds additional classes to the body tag
     *
     * @param array $classes the current classes as provided by WordPress
     * @return array
     *
     *--------------------------------------------------------------------------------------*/
    
    function add_body_classes($classes)
    {
        global $wp_query;
        
        $vars = $wp_query->query_vars;
        
        if (empty($vars['templatename'])) return $classes; 
        
        $classes[] = 'ez-post-archives';
        $classes[] = 'ez-post-archives-' . $vars['post_type'];
        
        return $classes;
    }
    
    /*--------------------------------------------------------------------------------------
     *
     * filter_wp_title($title, $before, $sep)
     * - modifies the output of wp_title() on archive pages
     *
     * @param string $title the current page title
     * @param string $before text that shows before the title
     * @param string $after text that shows after the title
     * @return string
     *
     *--------------------------------------------------------------------------------------*/
    
    function filter_wp_title($title, $before, $sep)
    {
        global $wp_query;
        
        $vars = $wp_query->query_vars;
        
        if (empty($vars['templatename'])) return $title;
        
        $pt = get_post_type_object(get_query_var('post_type'));
        
        if (get_query_var('taxonomy') && get_query_var('term')) :
            $term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
            $title = $term->name;
        else :
            $title = $pt->labels->singular_name;
        endif;
            
        if (get_query_var('monthnum')) :
            return $title . ' Archives From ' . date('F Y', mktime(0, 0, 0, get_query_var('monthnum'), 1, get_query_var('year')));
        else :
            return $title . ' Archives From ' . get_query_var('year');
        endif;
    }
}