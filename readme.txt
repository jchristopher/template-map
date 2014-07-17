=== Template Map ===
Contributors: jchristopher
Donate link: http://example.com/
Tags: template, link
Requires at least: 3.9
Tested up to: 3.9.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automagic mapping of Page Templates to post IDs to facilitate better dynamic link generation

== Description ==

Template Map is a utility plugin designed to make the creation and management of navigation elements in your custom theme that much easier. It abstracts the need to hard code post IDs and the like from your global and header navigation systems (if you choose to not use Menus) for instance.

= For Example =

Many times you may use something like the following to build your main site navigation:

`<nav>
  <ul>
    <li>
      <a href="<?php echo get_permalink( 83 ); ?>">About</a>
    </li>
  </ul>
</nav>`

That works great during initial development, and continues to hold up when you migrate to production en masse for the first time. But what about when you're making subsequent updates and the post IDs in your development environment are different than those in production? You could take the time to sync the databases each and every time, but that's a bit overkill. Here's where Template Map comes in to play:

`<nav>
  <ul>
    <li>
      <?php $about_page_id = TemplateMap()->get_id_from_template( 'template-about.php' ); ?>
      <a href="<?php echo get_permalink( $about_page_id ); ?>">About</a>
    </li>
  </ul>
</nav>`

Template Map allows you to *dynamically* retreive your desired post ID based on the Page Template you told it to use. Page Template filenames rarely (if ever) change, so it's the core concept of Template Map's implementation. Everything works backwards from here.

Naturally this philosophy assumes your Page Template is used only once, it's important to keep that in mind. Modern client sites often use unique Page Templates for the 'parent' pages of each site section. 

= Current Site Section =

Template Map also makes it easier to determine whether the current page is within a site 'section' which is defined as a top level Page with a unique Page Template that has any number of child Pages and/or Custom Post Types within it. This is very useful when trying to set a 'current' state in your navigation, for example:

`<nav>
  <ul>
    <li class="<?php if( TemplateMap()->maybe_in_section( 'template-about.php' ) ) : ?> current<?php endif; ?>">
      <?php $about_page_id = TemplateMap()->get_id_from_template( 'template-about.php' ); ?>
      <a href="<?php echo get_permalink( $about_page_id ); ?>">About</a>
    </li>
  </ul>
</nav>`

Based on the same principle you can use this utility method to properly orient yourself when outputting conditional classes contingent on the current page being within a 'secton' on your site.

== Installation ==


1. Upload `templatemap` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Replace calls to `get_permalink( 83 )` with `get_permalink( TemplateMap()->get_id_from_template( 'template-about.php' ) )` where `template-about.php` is the Page you wish to link

== Frequently Asked Questions ==

= How do I define Custom Post Types within a section? =

There's a filter for that. `template_map_post_types` accepts two parameters, the second of which is the Page Template filename in question. You can conditionally return an array of CPT names that will be utilized when checking to see whether the current page is within the section in question.

== Changelog ==

= 1.0 =
* Initial release
