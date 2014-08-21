Contributors: matthewsimo, chriswallace
Donate link: https://upthemes.com/themes/creative/
Tags: portfolio,dribbble,post types
Requires at least: 3.8
Tested up to: 4.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The best way to get up and running with a portfolio on your very own WordPress website.

== Description ==

* Adds a portfolio post type (compatible with Jetpack)
* Adds a Project Type taxonomy (works like categories)
* Adds a Project Tag taxonomy (works like tags)
* Syncs your Dribbble shots to WordPress (imports old shots as well)

=== Credits ===

- The amazing team at [automattic.com](http://automattic.com/) for [jetpack](http://jetpack.me/) and, specifically, the Jetpack Portfolio post type.
- [Tammy Hart](http://www.tammyhartdesigns.com/) for http://zurb.com/forrst/posts/Dribbble_to_WordPress-wZv
- [The nice folks at array.is](https://array.is) for their [Jetpack Portfolio Polyfill](https://array.is/articles/designer/#install-array-portfolio) for which I've adapted for use here.

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'plugin-name'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard
5. Visit `General > Writing` settings and enable the `Portfolio Projects` option.
6. Add your dribbble username in the appropriate settings field.

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `plugin-name.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard
6. Visit `General > Writing` settings and enable the `Portfolio Projects` option.
7. Add your dribbble username in the appropriate settings field.

= Using FTP =

1. Download `freefolio.zip`
2. Extract the `plugin-name` directory to your computer
3. Upload the `plugin-name` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard
5. Visit `General > Writing` settings and enable the `Portfolio Projects` option.
6. Add your dribbble username in the appropriate settings field.

== Frequently Asked Questions ==

= How does the Dribbble importer work? =

The plugin sets a transient that expires every hour. It will check against this transient to see if it should look at your dribbble feed and see if it needs to import any posts.

If it does, it will create `jetpack-portfolio` posts for items that don't exist. It will also create two post meta key/values:

- `dribbble_link_url`: which holds the URL for the shot on dribbble
- `dribbble_image_url`: which holds the URL for the image src for the shot

= How do I use the portfolio shortcode? =

Shortcode options:

‘display_types': display Project Types. (true/false)
‘display_tags': display Project Tags. (true/false)
‘display_content': display project content. (true/false)
‘include_type': display specific Project Types. Defaults to all. (comma-separated list of Project Type slugs)
‘include_tag': display specific Project Tags. Defaults to all. (comma-separated list of Project Tag slugs)
‘columns': number of&nbsp;columns in shortcode. Defaults to 2. (number, 1-6)
‘showposts': number of projects to display. Defaults to all. (number)

Shortcode example:

[portfolio display_types=true display_tags=false include_type=ui-design,app-design columns=3 showposts=10]