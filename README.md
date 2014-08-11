dribbble-portfolio-importer
===========================

Imports your dribbble feed into the Jetpack Portfolio Custom Post Type, falls back and generates that CPT for you if the install doesn't have Jetpack activated.


*Still expirimental - Use at your own risk*

### Installation:

- Download or clone the repo inside your WP install's `plugins/` directory. 
- Then visit the WP Plugin Admin panel and activate the plugin.


### Configuration:

Since this monkey patches the Jetpack portfolio CPT, you'll need to visit the `General > Writing` settings and enable the `Portfolio Projects` option. You'll also need to add your dribbble username in the appropriate settings field.


### Functionality:

The plugin sets a transient that expires every hour. It will check against this transient to see if it should look at your dribbble feed and see if it needs to import any posts.

If it does, it will create `jetpack-portfolio` posts for items that don't exist. It will also create two post meta key/values:

- `dribbble_link_url`: which holds the URL for the shot on dribbble
- `dribbble_image_url`: which holds the URL for the image src for the shot


*THIS PLUGIN IS ONLY AN IMPORTER - YOU NEED TO DO TEMPLATING YOURSELF*

Shout out to:
- [Tammy Hart(http://www.tammyhartdesigns.com/) for http://zurb.com/forrst/posts/Dribbble_to_WordPress-wZv 
- [The nice folks at array.is](https://array.is) for https://array.is/articles/designer/#install-array-portfolio

Both of which I've adapted for use in this plugin. Yay Open Source! :heart: :metal: :rocket:


