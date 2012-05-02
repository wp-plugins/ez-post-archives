=== EZ Post Archives ===
Contributors: joncowherdesign
Tags: post archives, archives
Requires at least: 3.2.1
Tested up to: 3.3.1
Stable tag: trunk

Easily add custom post type and taxonomy archives to your theme

== Description ==

### Features

* Allows you to easily generate an archive list by year or month - very similar to the wp_get_archives() function
* Generates necessary rewrite rules
* Generates SEO friendly title tags for archive templates
* Allows use of archive templates (archive.php or archive-{post-type}.php)
* Allows easy generation of necessary template headings

### Generate archive list

    EZ_Post_Archives::get($args);
    
    $args = array(
        'post_type' => '',
        // the slug(s) of the custom post type.
        // must use an array if using multiple post types
        
        'limit' => 12,
        // sets the number of archives pulled in
        
        'type' => 'yearly',
        // either yearly or monthly
        
        'taxonomy' => null,
        // optional taxonomy
        
        'term' => null,
        // optional term
        
        'month_format' => '<a href="{{link}}">{{month}} {{year}}</a>',
        // format for monthly links
        
        'year_format' => '<a href="{{link}}">{{year}}</a>',
        // format for yearly links
        
        'month_name_format' => 'M'
        // see the PHP date() function for reference
    );
    
### Generate page heading

    EZ_Post_Archives::the_title();
    
### Template files

    archive-{post-slug}.php
    archive.php
    
### CSS Helpers

If you use the body_class() function, the plugin will add the following classes to your
body tag:

* ez-post-archives
* ez-post-archives-{post-slug}

### Know what template you're on

If you need to check if you are on an archive page use the is_post_type_archive() conditional function 
    
== Installation ==

1. Using the WordPress plugin installer tool simply upload the ez-post-archives.zip file

== Frequently Asked Questions ==

= My archive links aren't working! =

If you create a new post type after the plugin is installed you will need to flush the rewrite rules. You can do this
by going to Settings > Permalinks and simply clicking the "Save Changes" button.

== Changelog ==

- 1.3 =
* Fixed fatal error with get_called_class() in PHP version < 5.3

= 1.2 =
* Added support for multiple post types
* Fixed misc bugs

= 1.1 =
* Added support for taxonomy archives

= 1.0 =
* Initial version